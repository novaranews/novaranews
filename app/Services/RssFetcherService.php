<?php

namespace App\Services;

use App\Models\AiArticleGeneration;
use App\Models\NewsSource;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RssFetcherService
{
    /** Concurrent HTTP requests per batch (admin health check) — avoids nginx/php-fpm 502 from long sequential fetches */
    private const ADMIN_CHECK_POOL_SIZE = 25;

    /** Admin health check: long enough for slow international RSS (7s caused false errors on some hosts) */
    private const ADMIN_CHECK_TIMEOUT_SECONDS = 12;

    /**
     * @return array<int, array{id: int, status: string, http_code: int, message: string}>
     */
    public function checkAllSourcesHealth(): array
    {
        @set_time_limit(300);

        $sources = NewsSource::query()->select(['id', 'url'])->get();
        $results = [];

        foreach ($sources->chunk(self::ADMIN_CHECK_POOL_SIZE) as $chunk) {
            try {
                $responses = Http::pool(function (Pool $pool) use ($chunk) {
                    foreach ($chunk as $source) {
                        $pool->as((string) $source->id)
                            ->timeout(self::ADMIN_CHECK_TIMEOUT_SECONDS)
                            ->connectTimeout(5)
                            ->withHeaders($this->defaultHttpHeaders())
                            ->get($source->url);
                    }
                });
            } catch (\Throwable $e) {
                foreach ($chunk as $source) {
                    $results[] = $this->healthResultRow(
                        (int) $source->id,
                        'error',
                        0,
                        'Pool error: '.$e->getMessage()
                    );
                }

                continue;
            }

            foreach ($chunk as $source) {
                $id = (string) $source->id;
                $response = $responses[$id] ?? null;

                if ($response instanceof \Throwable) {
                    $results[] = $this->healthResultRow(
                        (int) $source->id,
                        'error',
                        0,
                        $response->getMessage()
                    );

                    continue;
                }

                if (! $response instanceof Response) {
                    $results[] = $this->healthResultRow(
                        (int) $source->id,
                        'error',
                        0,
                        'No response'
                    );

                    continue;
                }

                if (! $response->successful()) {
                    $results[] = $this->healthResultRow(
                        (int) $source->id,
                        'error',
                        $response->status(),
                        'HTTP '.$response->status()
                    );

                    continue;
                }

                $body = $response->body();
                if ($this->bodyLooksLikeHtmlNotFeed($body)) {
                    $results[] = $this->healthResultRow(
                        (int) $source->id,
                        'error',
                        $response->status(),
                        'Response is HTML (bot wall or wrong URL), not RSS/Atom'
                    );

                    continue;
                }

                $items = $this->itemsFromFeedBody($body);

                $ok = count($items) > 0;
                $hasRichItem = collect($items)->contains(function (array $item): bool {
                    $desc = trim(strip_tags((string) ($item['description'] ?? '')));
                    $title = trim((string) ($item['title'] ?? ''));

                    return mb_strlen($desc) >= 60 || mb_strlen($title) >= 20;
                });

                $results[] = $this->healthResultRow(
                    (int) $source->id,
                    ($ok && $hasRichItem) ? 'ok' : 'error',
                    $response->status(),
                    ! $ok
                        ? 'Feed parse failed or returned no items'
                        : ($hasRichItem ? 'Feed parsed successfully' : 'Feed parsed but content is too thin')
                );
            }
        }

        return $results;
    }

    /**
     * @return array{id: int, status: string, http_code: int, message: string}
     */
    private function healthResultRow(int $id, string $status, int $httpCode, string $message): array
    {
        return [
            'id' => $id,
            'status' => $status,
            'http_code' => $httpCode,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminHealthCheckHttpOptions(): array
    {
        if (! defined('CURL_IPRESOLVE_V4')) {
            return [];
        }

        // Prefer IPv4: some hosts fail TLS or time out over IPv6 on datacenter IPs.
        return ['curl' => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4]];
    }

    private function bodyLooksLikeHtmlNotFeed(string $body): bool
    {
        $trim = ltrim($body);

        return str_starts_with(strtolower($trim), '<!doctype html')
            || str_starts_with(strtolower($trim), '<html');
    }

    /**
     * @return array<string, string>
     */
    private function defaultHttpHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];
    }

    /**
     * Fetch all active news sources and queue new items for generation.
     * Returns the number of new items queued.
     */
    public function fetchAll(): int
    {
        $sources = NewsSource::query()->where('is_active', true)->get();
        $total = 0;

        foreach ($sources as $source) {
            try {
                $total += $this->fetchSource($source);
            } catch (\Throwable $e) {
                Log::warning("RssFetcher: failed to fetch source [{$source->id}] {$source->name}: {$e->getMessage()}");
            }
        }

        return $total;
    }

    public function fetchByLocale(string $locale): int
    {
        $sources = NewsSource::query()
            ->where('is_active', true)
            ->where('locale', $locale)
            ->get();

        $total = 0;
        foreach ($sources as $source) {
            try {
                $total += $this->fetchSource($source);
            } catch (\Throwable $e) {
                Log::warning("RssFetcher: failed to fetch source [{$source->id}] {$source->name}: {$e->getMessage()}");
            }
        }

        return $total;
    }

    public function fetchByCategory(string $categoryKey): int
    {
        $sources = NewsSource::query()
            ->where('is_active', true)
            ->where('category_key', $categoryKey)
            ->get();

        $total = 0;
        foreach ($sources as $source) {
            try {
                $total += $this->fetchSource($source);
            } catch (\Throwable $e) {
                Log::warning("RssFetcher: failed to fetch source [{$source->id}] {$source->name}: {$e->getMessage()}");
            }
        }

        return $total;
    }

    public function fetchByLocaleAndCategory(string $locale, string $categoryKey): int
    {
        $sources = NewsSource::query()
            ->where('is_active', true)
            ->where('locale', $locale)
            ->where('category_key', $categoryKey)
            ->get();

        $total = 0;
        foreach ($sources as $source) {
            try {
                $total += $this->fetchSource($source);
            } catch (\Throwable $e) {
                Log::warning("RssFetcher: failed to fetch source [{$source->id}] {$source->name}: {$e->getMessage()}");
            }
        }

        return $total;
    }

    /**
     * Fetch a single news source and queue new items.
     * At most $maxPerSource new items are queued per fetch to avoid queue flooding.
     */
    public function fetchSource(NewsSource $source, int $maxPerSource = 10): int
    {
        $items = $this->parseRss($source->url);

        if (empty($items)) {
            return 0;
        }

        // Feeds are newest-first; take at most $maxPerSource items per run
        $items = array_slice($items, 0, $maxPerSource);

        $queued = 0;

        foreach ($items as $item) {
            $guid = $item['guid'] ?? $item['url'] ?? null;

            if (! $guid) {
                continue;
            }

            // Guardrail: AI-category feeds can contain broad/off-topic posts on some publishers.
            // Skip items that clearly do not look AI-related before they reach generation.
            if (! $this->isItemRelevantToSourceCategory($item, $source)) {
                continue;
            }

            // Dedup 1: same source, same GUID (primary check)
            // withTrashed() ensures soft-deleted records still prevent re-processing.
            $existsByGuid = AiArticleGeneration::withTrashed()
                ->where('news_source_id', $source->id)
                ->where('source_guid', $guid)
                ->exists();

            if ($existsByGuid) {
                continue;
            }

            // Dedup 2: same URL from any source in the same language (cross-source dedup)
            // Prevents writing the same article twice when multiple sources cover the same story.
            // withTrashed() ensures soft-deleted records still prevent re-processing.
            $itemUrl = mb_substr($item['url'] ?? '', 0, 2048) ?: null;
            if ($itemUrl) {
                $existsByUrl = AiArticleGeneration::withTrashed()
                    ->where('source_locale', $source->locale)
                    ->where('source_url', $itemUrl)
                    ->exists();

                if ($existsByUrl) {
                    continue;
                }
            }

            $text = trim(strip_tags($item['description'] ?? ''));
            if (strlen($text) < 30) {
                $text = $item['title'] ?? '';
            }

            // Pre-scrape the full article body when the RSS excerpt is thin.
            // A richer source_text means the bot has real content to work with
            // instead of padding a 50-word description to 450+ words.
            if ($itemUrl && mb_strlen($text) < 600) {
                $fullBody = $this->fetchArticleBody($itemUrl);
                if (mb_strlen($fullBody) > mb_strlen($text)) {
                    $text = $fullBody;
                }
            }

            AiArticleGeneration::query()->create([
                'news_source_id' => $source->id,
                'category_id' => null, // resolved later by generator
                'source_title' => mb_substr($item['title'] ?? 'Untitled', 0, 255),
                'source_url' => mb_substr($item['url'] ?? '', 0, 2048) ?: null,
                'source_guid' => mb_substr($guid, 0, 512),
                'source_locale' => $source->locale,
                'source_text' => mb_substr($text, 0, 10000),
                'source_packets' => [
                    'category_key' => $source->category_key,
                    'published_at' => $item['published_at'] ?? null,
                ],
                'target_locales' => [$source->locale], // article is generated in source language only
                'model' => null,
                'status' => AiArticleGeneration::STATUS_PENDING,
            ]);

            $queued++;
        }

        $source->update(['last_fetched_at' => now()]);

        return $queued;
    }

    /**
     * Parse an RSS/Atom feed URL and return items as arrays.
     *
     * @return array<int, array{title: string, url: ?string, guid: ?string, description: ?string, published_at: ?string}>
     */
    public function parseRss(string $url, int $timeoutSeconds = 15): array
    {
        try {
            $response = Http::timeout($timeoutSeconds)
                ->withHeaders($this->defaultHttpHeaders())
                ->get($url);

            if (! $response->successful()) {
                Log::warning("RssFetcher: HTTP {$response->status()} for {$url}");
                return [];
            }

            $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                Log::warning("RssFetcher: failed to parse XML for {$url}");
                return [];
            }
        } catch (\Throwable $e) {
            Log::warning("RssFetcher: exception fetching {$url}: {$e->getMessage()}");
            return [];
        }

        return $this->parseLoadedXml($xml);
    }

    /**
     * @return array<int, array{title: string, url: ?string, guid: ?string, description: ?string, published_at: ?string}>
     */
    private function itemsFromFeedBody(string $body): array
    {
        $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            return [];
        }

        return $this->parseLoadedXml($xml);
    }

    private function parseLoadedXml(\SimpleXMLElement $xml): array
    {
        $rootName = strtolower($xml->getName());

        if ($rootName === 'feed') {
            return $this->parseAtom($xml);
        }

        return $this->parseRssChannel($xml);
    }

    private function parseRssChannel(\SimpleXMLElement $xml): array
    {
        $items = [];
        $channel = $xml->channel ?? $xml;

        foreach ($channel->item as $item) {
            $items[] = [
                'title' => $this->text($item->title),
                'url' => $this->text($item->link) ?: $this->text($item->guid),
                'guid' => $this->text($item->guid) ?: $this->text($item->link),
                'description' => $this->rssItemDescription($item),
                'published_at' => $this->text($item->pubDate),
            ];
        }

        return $items;
    }

    private function rssItemDescription(\SimpleXMLElement $item): string
    {
        $plain = $this->text($item->description);
        if ($plain !== '') {
            return $plain;
        }

        $contentNs = 'http://purl.org/rss/1.0/modules/content/';
        $encoded = $item->children($contentNs)->encoded ?? null;

        return $encoded !== null ? $this->text($encoded) : '';
    }

    private function parseAtom(\SimpleXMLElement $xml): array
    {
        $items = [];
        $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        foreach ($xml->entry as $entry) {
            $url = null;
            foreach ($entry->link as $link) {
                // Atom: missing rel means "alternate" (RFC 4287).
                $rel = strtolower(trim((string) ($link['rel'] ?? '')));
                if ($rel === '' || $rel === 'alternate') {
                    $href = (string) ($link['href'] ?? '');
                    if ($href !== '') {
                        $url = $href;
                        break;
                    }
                }
            }
            $url = $url ?: (string) ($entry->link['href'] ?? '');

            $summary = $this->text($entry->summary);
            $content = $this->text($entry->content);

            $items[] = [
                'title' => $this->text($entry->title),
                'url' => $url,
                'guid' => $this->text($entry->id) ?: $url,
                'description' => $summary !== '' ? $summary : $content,
                'published_at' => $this->text($entry->published) ?: $this->text($entry->updated),
            ];
        }

        return $items;
    }

    private function text(mixed $node): string
    {
        if ($node === null) {
            return '';
        }
        return trim((string) $node);
    }

    /**
     * Prevent off-topic queue entries for categories that need stricter relevance.
     *
     * @param  array{title?: string, description?: ?string}  $item
     */
    private function isItemRelevantToSourceCategory(array $item, NewsSource $source): bool
    {
        $categoryKey = (string) ($source->category_key ?? '');
        if ($categoryKey === '') {
            return true;
        }

        $title = (string) ($item['title'] ?? '');
        $desc = trim(strip_tags((string) ($item['description'] ?? '')));
        $haystack = $this->normalizeForKeywordMatch($title.' '.$desc);
        if ($haystack === '') {
            return true;
        }

        $locale = (string) ($source->locale ?? '');
        $keywords = $this->categoryKeywords($categoryKey, $locale);
        if ($keywords === []) {
            return true;
        }

        foreach ($keywords as $keyword) {
            if ($this->haystackContainsKeyword($haystack, $keyword)) {
                return true;
            }
        }

        // Dedicated category feeds can still have noisy items. For Google News
        // we enforce strict keyword matching to avoid off-topic queue entries.
        if ($this->isGoogleNewsSource($source)) {
            return false;
        }

        // For broad technology category, allow trusted non-Google curated feeds
        // even when keyword hit is missing.
        if ($categoryKey === 'technology') {
            return true;
        }

        // For narrower categories (AI, mobile, game, software, hardware),
        // require at least one category keyword match.
        return false;
    }

    private function normalizeForKeywordMatch(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $ascii = Str::ascii($decoded);
        return mb_strtolower($ascii, 'UTF-8');
    }

    /**
     * Short single-word keywords (≤3 chars, e.g. "ai", "ki", "ia") are matched as whole words
     * to avoid false positives like "said", "available", "detail" matching "ai".
     * Longer or multi-word keywords use plain substring match.
     */
    private function haystackContainsKeyword(string $haystack, string $keyword): bool
    {
        $wordCount = substr_count(trim($keyword), ' ') + 1;
        if ($wordCount === 1 && strlen($keyword) <= 3) {
            return (bool) preg_match('/(?<![a-z])'.preg_quote($keyword, '/').'(?![a-z])/', $haystack);
        }

        return str_contains($haystack, $keyword);
    }

    /**
     * @return array<int, string>
     */
    private function isGoogleNewsSource(NewsSource $source): bool
    {
        $name = mb_strtolower((string) ($source->name ?? ''), 'UTF-8');
        $url = mb_strtolower((string) ($source->url ?? ''), 'UTF-8');

        return str_contains($name, 'google news') || str_contains($url, 'news.google.com');
    }

    /**
     * @return array<int, string>
     */
    private function categoryKeywords(string $categoryKey, string $locale): array
    {
        return match ($categoryKey) {
            'artificial-intelligence' => $this->aiCategoryKeywords($locale),
            'technology' => $this->technologyCategoryKeywords($locale),
            'mobile' => $this->mobileCategoryKeywords($locale),
            'game' => $this->gameCategoryKeywords($locale),
            'software' => $this->softwareCategoryKeywords($locale),
            'hardware' => $this->hardwareCategoryKeywords($locale),
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private function aiCategoryKeywords(string $locale): array
    {
        $global = [
            'artificial intelligence', 'ai', 'machine learning', 'deep learning',
            'neural', 'llm', 'gpt', 'chatgpt', 'openai', 'anthropic',
            'gemini', 'copilot', 'claude',
        ];

        $byLocale = match ($locale) {
            'tr' => ['yapay zeka', 'uretken ai', 'uretken yapay zeka', 'buyuk dil modeli', 'dil modeli'],
            'de' => ['kunstliche intelligenz', 'ki', 'sprachmodell'],
            'fr' => ['intelligence artificielle', 'ia', 'modele de langage'],
            'es' => ['inteligencia artificial', 'ia', 'modelo de lenguaje'],
            default => [],
        };

        return array_values(array_unique(array_merge($global, $byLocale)));
    }

    /**
     * @return array<int, string>
     */
    private function technologyCategoryKeywords(string $locale): array
    {
        $global = [
            'technology', 'tech', 'innovation', 'startup', 'science', 'research',
            'digital', 'internet', 'cybersecurity', 'software', 'hardware',
            'ai', 'mobile', 'smartphone', 'chip', 'processor',
        ];

        $byLocale = match ($locale) {
            'tr' => ['teknoloji', 'yenilik', 'girisim', 'bilim', 'dijital', 'siber', 'yazilim', 'donanim'],
            'de' => ['technologie', 'innovation', 'start-up', 'wissenschaft', 'digital', 'sicherheit'],
            'fr' => ['technologie', 'innovation', 'startup', 'science', 'numerique', 'cybersecurite'],
            'es' => ['tecnologia', 'innovacion', 'startup', 'ciencia', 'digital', 'ciberseguridad'],
            default => [],
        };

        return array_values(array_unique(array_merge($global, $byLocale)));
    }

    /**
     * @return array<int, string>
     */
    private function mobileCategoryKeywords(string $locale): array
    {
        $global = [
            'mobile', 'smartphone', 'phone', 'iphone', 'android', 'ios', 'ipad',
            'samsung', 'xiaomi', 'huawei', 'oneplus', 'pixel', 'mobile app',
            'app update', 'play store', 'app store',
        ];

        $byLocale = match ($locale) {
            'tr' => ['mobil', 'akilli telefon', 'telefon', 'uygulama', 'guncelleme'],
            'de' => ['mobil', 'smartphone', 'handy', 'app', 'aktualisierung'],
            'fr' => ['mobile', 'smartphone', 'telephone', 'application', 'mise a jour'],
            'es' => ['movil', 'smartphone', 'telefono', 'aplicacion', 'actualizacion'],
            default => [],
        };

        return array_values(array_unique(array_merge($global, $byLocale)));
    }

    /**
     * @return array<int, string>
     */
    private function gameCategoryKeywords(string $locale): array
    {
        $global = [
            'game', 'gaming', 'video game', 'playstation', 'xbox', 'nintendo',
            'steam', 'epic games', 'pc gamer', 'dlc', 'trailer', 'esports',
        ];

        $byLocale = match ($locale) {
            'tr' => ['oyun', 'video oyun', 'oyuncu', 'konsol'],
            'de' => ['spiel', 'videospiel', 'gaming', 'konsole'],
            'fr' => ['jeu', 'jeu video', 'gaming', 'console'],
            'es' => ['juego', 'videojuego', 'gaming', 'consola'],
            default => [],
        };

        return array_values(array_unique(array_merge($global, $byLocale)));
    }

    /**
     * @return array<int, string>
     */
    private function softwareCategoryKeywords(string $locale): array
    {
        $global = [
            'software', 'app', 'application', 'release', 'update', 'version',
            'developer', 'github', 'framework', 'library', 'api', 'open source',
            'programming', 'code',
        ];

        $byLocale = match ($locale) {
            'tr' => ['yazilim', 'uygulama', 'surum', 'guncelleme', 'gelistirici', 'acik kaynak', 'kod'],
            'de' => ['software', 'anwendung', 'version', 'update', 'entwickler', 'open source', 'programmierung'],
            'fr' => ['logiciel', 'application', 'version', 'mise a jour', 'developpeur', 'open source', 'programmation'],
            'es' => ['software', 'aplicacion', 'version', 'actualizacion', 'desarrollador', 'codigo abierto', 'programacion'],
            default => [],
        };

        return array_values(array_unique(array_merge($global, $byLocale)));
    }

    /**
     * Fetch the full article body from the given URL and return clean plain text.
     * Returns an empty string on any failure so callers can fall back silently.
     */
    private function fetchArticleBody(string $url): string
    {
        try {
            $response = Http::timeout(12)
                ->connectTimeout(6)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($url);

            if (! $response->successful()) {
                return '';
            }

            $html = $response->body();
            if (strlen($html) < 200) {
                return '';
            }

            return $this->extractArticleText($html, 4000);

        } catch (\Throwable $e) {
            Log::debug("RssFetcher: skipping article pre-fetch for {$url}: {$e->getMessage()}");
            return '';
        }
    }

    /**
     * Strip HTML boilerplate and return clean readable text up to $maxChars.
     */
    private function extractArticleText(string $html, int $maxChars = 9000): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $html) ?? $html;
        $html = preg_replace('/<nav\b[^>]*>.*?<\/nav>/si', '', $html) ?? $html;
        $html = preg_replace('/<header\b[^>]*>.*?<\/header>/si', '', $html) ?? $html;
        $html = preg_replace('/<footer\b[^>]*>.*?<\/footer>/si', '', $html) ?? $html;
        $html = preg_replace('/<aside\b[^>]*>.*?<\/aside>/si', '', $html) ?? $html;
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/<\/?(p|div|h[1-6]|li|br|tr|blockquote|section|article)[^>]*>/i', "\n", $html) ?? $html;

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        return $text;
    }

    /**
     * @return array<int, string>
     */
    private function hardwareCategoryKeywords(string $locale): array
    {
        $global = [
            'hardware', 'cpu', 'gpu', 'processor', 'chip', 'chipset', 'ram',
            'motherboard', 'graphics card', 'nvidia', 'amd', 'intel', 'benchmark',
            'laptop', 'desktop',
        ];

        $byLocale = match ($locale) {
            'tr' => ['donanim', 'islemci', 'ekran karti', 'bellek', 'anakart'],
            'de' => ['hardware', 'prozessor', 'grafikkarte', 'speicher', 'mainboard'],
            'fr' => ['materiel', 'processeur', 'carte graphique', 'memoire', 'carte mere'],
            'es' => ['hardware', 'procesador', 'tarjeta grafica', 'memoria', 'placa base'],
            default => [],
        };

        return array_values(array_unique(array_merge($global, $byLocale)));
    }
}
