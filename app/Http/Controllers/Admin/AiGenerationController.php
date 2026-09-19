<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateArticleJob;
use App\Jobs\RunBotForLocaleJob;
use App\Models\AiArticleGeneration;
use App\Models\Category;
use App\Models\NewsSource;
use App\Models\Setting;
use App\Services\RssFetcherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiGenerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $locale = (string) $request->query('locale', '');

        $generations = AiArticleGeneration::query()
            ->with(['newsSource', 'article.translations', 'article.category.translations'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($locale !== '', fn ($q) => $q->where('source_locale', $locale))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $locales = config('novaranews.locales', []);

        $stats = [
            'pending'    => AiArticleGeneration::query()->where('status', 'pending')->count(),
            'processing' => AiArticleGeneration::query()->where('status', 'processing')->count(),
            'done'       => AiArticleGeneration::query()->where('status', 'done')->count(),
            'failed'     => AiArticleGeneration::query()->where('status', 'failed')->count(),
        ];

        $currentModel    = Setting::get('bot_model', 'claude-sonnet-4-6');
        $botEnabled      = Setting::get('bot_enabled', true);
        $autoPublish     = Setting::get('bot_auto_publish', false);
        $articlesPerRun  = (int) Setting::get('bot_articles_per_run', 20);
        $categories      = Category::query()->with('translations')->orderBy('sort_order')->get();

        return view('admin.ai-generations.index', compact(
            'generations', 'status', 'locale', 'locales',
            'stats', 'currentModel', 'botEnabled', 'autoPublish', 'articlesPerRun', 'categories'
        ));
    }

    public function runNow(Request $request): RedirectResponse
    {
        $validCategoryKeys = Category::orderedKeys()->all();

        $validated = $request->validate([
            'locale'       => ['nullable', 'in:'.implode(',', array_merge([''], config('novaranews.locales', [])))],
            'category_key' => ['nullable', 'in:'.implode(',', array_merge([''], $validCategoryKeys))],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $locale      = $validated['locale'] ?: null;
        $categoryKey = $validated['category_key'] ?: null;
        $limit       = (int) ($validated['limit'] ?? Setting::get('bot_articles_per_run', 20));

        RunBotForLocaleJob::dispatch($locale, $limit, $categoryKey);

        $localeName = $locale ? strtoupper($locale) : __('site.admin_bot_started_all');
        return back()->with('success', __('site.admin_bot_started', ['locale' => $localeName, 'limit' => $limit]));
    }

    public function manualGenerate(Request $request): RedirectResponse
    {
        $validCategoryKeys = Category::orderedKeys()->all();
        $validated = $request->validate([
            'topic'        => ['nullable', 'string', 'min:5', 'max:300'],
            'source_url'   => ['required', 'url', 'max:2048'],
            'context'      => ['nullable', 'string', 'max:5000'],
            'category_key' => ['required', 'in:'.implode(',', $validCategoryKeys)],
            'locale'       => ['required', 'in:'.implode(',', config('novaranews.locales', ['en']))],
        ]);

        $topic = trim((string) ($validated['topic'] ?? ''));
        $sourceUrl = trim((string) ($validated['source_url'] ?? ''));
        $context = trim((string) ($validated['context'] ?? ''));

        $sourceTitle = $topic;
        $sourceText = $context;

        if (! $this->isUrlSafeToFetch($sourceUrl)) {
            return back()->withErrors([__('site.admin_manual_generation_url_unsafe')])->withInput();
        }

        $fetched = $this->extractContentFromSourceUrl($sourceUrl);
        if (($sourceTitle === '') && ($fetched['title'] ?? '') !== '') {
            $sourceTitle = (string) $fetched['title'];
        }

        if (($fetched['content'] ?? '') === '' && $context === '' && $sourceTitle === '') {
            return back()->withErrors([__('site.admin_manual_generation_url_unreadable')])->withInput();
        }

        $parts = [];
        if (($fetched['content'] ?? '') !== '') {
            $parts[] = "SOURCE_URL: {$sourceUrl}\n\nURL_EXTRACTED_CONTENT:\n".$fetched['content'];
        } else {
            $parts[] = "SOURCE_URL: {$sourceUrl}";
        }
        if ($context !== '') {
            $parts[] = "EDITOR_CONTEXT:\n".$context;
        }
        $sourceText = implode("\n\n---\n\n", $parts);

        if ($sourceTitle === '') {
            $sourceTitle = 'Manual URL Story';
        }

        $gen = AiArticleGeneration::query()->create([
            'news_source_id' => null,
            'source_title'   => $sourceTitle,
            'source_text'    => $sourceText,
            'source_url'     => $sourceUrl !== '' ? $sourceUrl : null,
            'source_guid'    => 'manual-'.uniqid(),
            'source_locale'  => $validated['locale'],
            'source_packets' => [
                'category_key' => $validated['category_key'],
                'manual_mode' => 'url_only',
                'url_ingested' => true,
            ],
            'target_locales' => [$validated['locale']],
            'status'         => AiArticleGeneration::STATUS_PENDING,
        ]);

        GenerateArticleJob::dispatch($gen->id);

        $cat = Category::query()->where('key', $validated['category_key'])->with('translations')->first();
        $queuedCategory = $cat
            ? (string) ($cat->translate($validated['locale'])?->name ?? $cat->translate('en')?->name ?? $validated['category_key'])
            : ucfirst((string) $validated['category_key']);

        return back()->with('success', __('site.admin_manual_generation_queued', [
            'category' => $queuedCategory,
            'topic' => $sourceTitle,
        ]));
    }

    /**
     * Prevent SSRF from admin URL ingestion.
     */
    private function isUrlSafeToFetch(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        // Block localhost/private/link-local patterns.
        if (preg_match('/^(localhost|127\.|0\.|10\.|192\.168\.|169\.254\.|::1$|fc00:|fe80:)/i', $host)) {
            return false;
        }
        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./i', $host)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{title: string, content: string}
     */
    private function extractContentFromSourceUrl(string $url): array
    {
        try {
            $response = Http::timeout(20)
                ->connectTimeout(7)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($url);

            if (! $response->successful()) {
                return ['title' => '', 'content' => ''];
            }

            $body = (string) $response->body();
            $title = '';

            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
                $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $title = Str::limit(preg_replace('/\s+/u', ' ', $title) ?: '', 255, '');
            }

            // Remove script/style/svg and extract human-readable text blocks.
            $plain = preg_replace('/<(script|style|noscript|svg)[^>]*>.*?<\/\1>/is', ' ', $body) ?? $body;
            $plain = preg_replace('/<\/(p|div|article|section|h1|h2|h3|li)>/i', "\n", $plain) ?? $plain;
            $plain = html_entity_decode(strip_tags($plain), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plain = preg_replace('/\r\n?/', "\n", $plain) ?? $plain;
            $plain = preg_replace('/[ \t]+/u', ' ', $plain) ?? $plain;

            $lines = preg_split('/\n+/', $plain) ?: [];
            $lines = array_values(array_filter(array_map(
                fn (string $line): string => trim($line),
                $lines
            ), fn (string $line): bool => mb_strlen($line) >= 40));

            $content = implode("\n", array_slice($lines, 0, 80));
            $content = Str::limit($content, 9000, '');

            return [
                'title' => $title,
                'content' => $content,
            ];
        } catch (\Throwable) {
            return ['title' => '', 'content' => ''];
        }
    }

    public function show(AiArticleGeneration $aiGeneration): View
    {
        $aiGeneration->load(['newsSource', 'article.translations', 'article.category.translations', 'approvedBy']);
        return view('admin.ai-generations.show', compact('aiGeneration'));
    }

    public function retry(AiArticleGeneration $aiGeneration): RedirectResponse
    {
        if (! in_array($aiGeneration->status, [AiArticleGeneration::STATUS_FAILED, AiArticleGeneration::STATUS_PENDING], true)) {
            return back()->withErrors([__('site.admin_retry_invalid_status')]);
        }

        $aiGeneration->update([
            'status' => AiArticleGeneration::STATUS_PENDING,
            'error_message' => null,
        ]);

        GenerateArticleJob::dispatch($aiGeneration->id);

        return back()->with('success', __('site.admin_retry_queued'));
    }

    public function approve(Request $request, AiArticleGeneration $aiGeneration): RedirectResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $aiGeneration->isDone() || ! $aiGeneration->article_id) {
            return back()->withErrors([__('site.admin_approval_invalid')]);
        }

        $aiGeneration->update([
            'approved_by_user_id' => $request->user()->id,
            'approval_note' => $request->input('note'),
            'approved_at' => now(),
        ]);

        // Publish the article
        $aiGeneration->article?->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        \App\Support\SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.ai-generations.index')->with('success', __('site.admin_article_approved'));
    }

    public function destroy(AiArticleGeneration $aiGeneration): RedirectResponse
    {
        $aiGeneration->delete();
        return redirect()->route('admin.ai-generations.index')->with('success', __('site.admin_record_deleted'));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->withErrors([__('site.admin_no_records_selected')]);
        }
        $count = AiArticleGeneration::query()->whereIn('id', $ids)->delete();
        return redirect()->route('admin.ai-generations.index')->with('success', __('site.admin_records_deleted', ['count' => $count]));
    }

    public function clearByStatus(Request $request): RedirectResponse
    {
        $status = $request->input('status');
        $allowed = [AiArticleGeneration::STATUS_PENDING, AiArticleGeneration::STATUS_FAILED];
        if (! in_array($status, $allowed, true)) {
            return back()->withErrors([__('site.admin_invalid_status')]);
        }
        $count = AiArticleGeneration::query()->where('status', $status)->delete();
        $label = $status === 'pending' ? __('site.admin_status_pending') : __('site.admin_status_failed');
        return redirect()->route('admin.ai-generations.index')->with('success', __('site.admin_status_cleared', ['status' => $label, 'count' => $count]));
    }

    public function sources(Request $request): View
    {
        $search      = trim((string) $request->query('search', ''));
        $isActive    = $request->query('is_active', '');
        $locale      = (string) $request->query('locale', '');
        $categoryKey = (string) $request->query('category_key', '');

        $sources = NewsSource::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) =>
                $q2->where('name', 'like', '%'.$search.'%')
                   ->orWhere('url', 'like', '%'.$search.'%')
            ))
            ->when($isActive !== '', fn ($q) => $q->where('is_active', (bool) $isActive))
            ->when($locale !== '', fn ($q) => $q->where('locale', $locale))
            ->when($categoryKey !== '', fn ($q) => $q->where('category_key', $categoryKey))
            ->orderBy('locale')
            ->orderBy('name')
            ->get();

        $locales    = config('novaranews.locales', []);
        $categories = Category::query()->with('translations')->orderBy('sort_order')->get();

        return view('admin.ai-generations.sources', compact('sources', 'locales', 'categories', 'search', 'isActive', 'locale', 'categoryKey'));
    }

    public function sourceCheckAll(RssFetcherService $fetcher): JsonResponse
    {
        // Parallel HTTP pool in RssFetcherService (avoids 502 from sequential 15s×N timeouts).
        return response()->json($fetcher->checkAllSourcesHealth());
    }

    public function sourceStore(Request $request): RedirectResponse
    {
        $validCategoryKeys = Category::orderedKeys()->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:2048'],
            'locale' => ['required', 'in:'.implode(',', config('novaranews.locales', ['en']))],
            'category_key' => ['nullable', 'in:'.implode(',', array_merge([''], $validCategoryKeys))],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['category_key'] = $validated['category_key'] ?: null;

        NewsSource::query()->create($validated);

        return redirect()->route('admin.ai-generations.sources')->with('success', __('site.admin_source_added'));
    }

    public function sourceToggle(NewsSource $newsSource): RedirectResponse
    {
        $newsSource->update(['is_active' => ! $newsSource->is_active]);
        return back()->with('success', __('site.admin_source_status_updated'));
    }

    public function sourceDestroy(NewsSource $newsSource): RedirectResponse
    {
        $newsSource->delete();
        return redirect()->route('admin.ai-generations.sources')->with('success', __('site.admin_source_deleted'));
    }

    public function sourceBulkDestroy(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (empty($ids)) {
            return back()->withErrors([__('site.admin_source_none_selected')]);
        }

        $count = NewsSource::query()->whereIn('id', $ids)->delete();

        return redirect()->route('admin.ai-generations.sources')->with('success', __('site.admin_sources_deleted', ['count' => $count]));
    }
}
