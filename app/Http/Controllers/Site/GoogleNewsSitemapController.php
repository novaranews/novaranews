<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use App\Support\SafeCache;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class GoogleNewsSitemapController extends Controller
{
    public const PER_FILE = 1000;
    private const CHUNK_SIZE = 500;

    public function __invoke(?int $page = 1): Response
    {
        $page = max(1, (int) ($page ?? 1));
        $publication = (string) Setting::site('publisher_name', config('novaranews.google_news_publication_name', config('app.name')));

        $pageEntries = self::newsEntriesPage($page);

        $xml = view('site.google-news-sitemap', [
            'entries' => $pageEntries,
            'publication' => $publication,
        ])->render();
        SafeCache::put('sitemap:last_hit:news', now()->toIso8601String(), now()->addDays(14));

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public static function pagesCount(): int
    {
        $n = self::newsEntriesCount();

        if ($n === 0) {
            return 0;
        }

        return (int) ceil($n / self::PER_FILE);
    }

    /**
     * @return Collection<int, array{loc: string, language: string, published_at: string, title: string}>
     */
    private static function newsEntriesPage(int $page): Collection
    {
        $start = ($page - 1) * self::PER_FILE;
        $end = $start + self::PER_FILE;
        $position = 0;
        $entries = collect();
        $locales = config('novaranews.locales', ['en']);
        $since = now()->subHours(48);

        $pushEntry = function (array $entry) use (&$position, $start, $end, $entries): void {
            if ($position >= $start && $position < $end) {
                $entries->push($entry);
            }
            $position++;
        };

        Article::query()
            ->published()
            ->where('content_type', 'news')
            ->where('published_at', '>=', $since)
            ->with(['translations'])
            ->orderByDesc('published_at')
            ->chunk(self::CHUNK_SIZE, function ($articles) use ($locales, $pushEntry): void {
                foreach ($articles as $article) {
                    foreach ($locales as $locale) {
                        $tr = $article->translations->firstWhere('locale', $locale);
                        if (! $tr) {
                            continue;
                        }
                        if ((bool) ($tr->robots_noindex ?? false)) {
                            continue;
                        }
                        $gnUrl = $article->publicUrl($locale);
                        if (! $gnUrl) {
                            continue;
                        }
                        $gnLang = match ($locale) {
                            'tr' => 'tr',
                            'de' => 'de',
                            'fr' => 'fr',
                            'es' => 'es',
                            default => 'en',
                        };
                        $pushEntry([
                            'loc' => $gnUrl,
                            'language' => $gnLang,
                            'published_at' => $article->published_at?->toAtomString() ?? now()->toAtomString(),
                            'title' => $tr->title,
                        ]);
                    }
                }
            });

        return $entries;
    }

    private static function newsEntriesCount(): int
    {
        return LocalizedNewsSitemapController::recentNewsTranslationCountAllLocales();
    }
}
