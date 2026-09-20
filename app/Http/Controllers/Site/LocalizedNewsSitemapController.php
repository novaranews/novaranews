<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Setting;
use App\Support\SafeCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class LocalizedNewsSitemapController extends Controller
{
    public const PER_FILE = 1000;
    private const CHUNK_SIZE = 500;

    public function __invoke(string $locale, ?int $page = 1): Response|RedirectResponse
    {
        $page = max(1, (int) ($page ?? 1));
        if ($page === 1 && request()->routeIs('news-sitemap.locale.page')) {
            return redirect()->route('news-sitemap.locale', ['locale' => $locale], 301);
        }

        $totalPages = self::pagesCount($locale);
        if ($totalPages === 0 && $page > 1) {
            abort(404);
        }
        if ($totalPages > 0 && $page > $totalPages) {
            abort(404);
        }

        $publication = (string) Setting::site('publisher_name', config('novaranews.google_news_publication_name', config('app.name')));
        $entries = self::newsEntriesPage($locale, $page);

        $xml = view('site.google-news-sitemap', [
            'entries' => $entries,
            'publication' => $publication,
        ])->render();

        $hit = now()->toIso8601String();
        SafeCache::put("sitemap:last_hit:{$locale}:news", $hit, now()->addDays(14));
        SafeCache::put('sitemap:last_hit:news', $hit, now()->addDays(14));
        SafeCache::put('sitemap:last_ua:news', request()->userAgent() ?? '', now()->addDays(14));

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public static function pagesCount(string $locale): int
    {
        $n = self::newsEntriesCount($locale);

        if ($n === 0) {
            return 0;
        }

        return (int) ceil($n / self::PER_FILE);
    }

    /**
     * Number of translation rows listed in the 48-hour Google News window across all locales.
     * The admin summary/readiness view and the sitemap entries use the same definition.
     */
    public static function recentNewsTranslationCountAllLocales(): int
    {
        $locales = config('novaranews.locales', ['en']);
        $since = now()->subHours(48);

        return ArticleTranslation::query()
            ->whereIn('locale', $locales)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(function ($q) {
                $q->whereNull('robots_noindex')->orWhere('robots_noindex', false);
            })
            ->whereHas('article', function ($query) use ($since): void {
                $query->published()
                    ->whereColumn('articles.locale', 'article_translations.locale')
                    ->where('content_type', 'news')
                    ->where('published_at', '>=', $since);
            })
            ->count();
    }

    /**
     * @return Collection<int, array{loc: string, language: string, published_at: string, title: string}>
     */
    private static function newsEntriesPage(string $locale, int $page): Collection
    {
        $start = ($page - 1) * self::PER_FILE;
        $end = $start + self::PER_FILE;
        $position = 0;
        $entries = collect();
        $since = now()->subHours(48);

        $pushEntry = function (array $entry) use (&$position, $start, $end, $entries): void {
            if ($position >= $start && $position < $end) {
                $entries->push($entry);
            }
            $position++;
        };

        Article::query()
            ->published()
            ->where('locale', $locale)
            ->where('content_type', 'news')
            ->where('published_at', '>=', $since)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderByDesc('published_at')
            ->chunk(self::CHUNK_SIZE, function ($articles) use ($locale, $pushEntry): void {
                foreach ($articles as $article) {
                    $tr = $article->translations->first();
                    if (! $tr) {
                        continue;
                    }
                    if ((bool) ($tr->robots_noindex ?? false)) {
                        continue;
                    }
                    if (! is_string($tr->slug) || trim($tr->slug) === '') {
                        continue;
                    }
                    $gnUrl = $article->publicUrl($locale);
                    if (! $gnUrl) {
                        continue;
                    }
                    $pushEntry([
                        'loc' => $gnUrl,
                        'language' => self::newsLanguageFromLocale($locale),
                        'published_at' => $article->published_at?->toAtomString() ?? now()->toAtomString(),
                        'title' => (string) $tr->title,
                    ]);
                }
            });

        return $entries;
    }

    private static function newsEntriesCount(string $locale): int
    {
        $since = now()->subHours(48);

        return ArticleTranslation::query()
            ->where('locale', $locale)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(function ($q) {
                $q->whereNull('robots_noindex')->orWhere('robots_noindex', false);
            })
            ->whereHas('article', function ($query) use ($since, $locale): void {
                $query->published()
                    ->where('locale', $locale)
                    ->where('content_type', 'news')
                    ->where('published_at', '>=', $since);
            })
            ->count();
    }

    private static function newsLanguageFromLocale(string $locale): string
    {
        return match ($locale) {
            'tr' => 'tr',
            'de' => 'de',
            'fr' => 'fr',
            'es' => 'es',
            default => 'en',
        };
    }
}
