<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\StaticPage;
use App\Models\User;
use App\Support\SafeCache;
use Illuminate\Http\Response;

class SitemapIndexController extends Controller
{
    public function __invoke(): Response
    {
        $base    = rtrim((string) config('app.url'), '/');
        $now     = now()->toAtomString();
        $locales = config('novaranews.locales', ['en']);
        $types   = \App\Http\Controllers\Site\LocalizedSitemapController::TYPES;

        $items = [];

        foreach ($locales as $locale) {
            // Regular sitemaps (pages, categories, articles, authors) — paginated
            foreach ($types as $type) {
                $pages = \App\Http\Controllers\Site\LocalizedSitemapController::pagesCount($locale, $type);

                // Skip types with no content — Google flags 0-URL sitemaps as errors
                if ($pages === 0) {
                    continue;
                }

                $lastmod = $this->contentLastmod($locale, $type, $now);

                for ($i = 1; $i <= $pages; $i++) {
                    $loc     = $i === 1
                        ? "{$base}/sitemap-{$locale}-{$type}.xml"
                        : "{$base}/sitemap-{$locale}-{$type}-{$i}.xml";
                    $items[] = "  <sitemap>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </sitemap>";
                }
            }

            // Google News sitemap — paginated
            $newsPages = \App\Http\Controllers\Site\LocalizedNewsSitemapController::pagesCount($locale);

            // Skip news sitemap when no articles published in the last 48 hours
            if ($newsPages > 0) {
                $newsLastmod = $this->newsLastmod($locale, $now);

                for ($i = 1; $i <= $newsPages; $i++) {
                    $loc     = $i === 1
                        ? "{$base}/news-sitemap-{$locale}.xml"
                        : "{$base}/news-sitemap-{$locale}-{$i}.xml";
                    $items[] = "  <sitemap>\n    <loc>{$loc}</loc>\n    <lastmod>{$newsLastmod}</lastmod>\n  </sitemap>";
                }
            }
        }

        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode("\n", $items)
            . "\n</sitemapindex>\n";

        SafeCache::put('sitemap:last_hit:index', now()->toIso8601String(), now()->addDays(14));
        SafeCache::put('sitemap:last_ua:index', request()->userAgent() ?? '', now()->addDays(14));

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    // lastmod for news sitemap — max published_at within the 48-hour window
    private function newsLastmod(string $locale, string $fallback): string
    {
        $cacheKey = "sitemap:content_lastmod:{$locale}:news";

        return SafeCache::remember($cacheKey, now()->addMinutes(10), function () use ($locale, $fallback): string {
            $ts = \App\Models\ArticleTranslation::query()
                ->where('article_translations.locale', $locale)
                ->whereNotNull('article_translations.slug')
                ->where('article_translations.slug', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('article_translations.robots_noindex')
                        ->orWhere('article_translations.robots_noindex', false);
                })
                ->whereHas('article', function ($q) {
                    $q->published()
                        ->whereColumn('articles.locale', 'article_translations.locale')
                        ->where('content_type', 'news')
                        ->where('published_at', '>=', now()->subHours(48));
                })
                ->join('articles', 'article_translations.article_id', '=', 'articles.id')
                ->max('articles.published_at');

            return $ts ? now()->parse($ts)->toAtomString() : $fallback;
        });
    }

    // Cache per type for 10 minutes to avoid hitting DB on every bot request
    private function contentLastmod(string $locale, string $type, string $fallback): string
    {
        $cacheKey = "sitemap:content_lastmod:{$locale}:{$type}";

        return SafeCache::remember($cacheKey, now()->addMinutes(10), function () use ($locale, $type, $fallback): string {
            $ts = match ($type) {
                'articles' => $this->articleContentLastmod($locale),
                'categories' => Category::query()->max('updated_at'),
                'pages'      => StaticPage::query()->where('is_active', true)->max('updated_at'),
                'authors'    => User::query()->whereNotNull('slug')->max('updated_at'),
                default      => null,
            };

            return $ts ? now()->parse($ts)->toAtomString() : $fallback;
        });
    }

    private function articleContentLastmod(string $locale): ?string
    {
        $articleQuery = Article::query()
            ->published()
            ->where('locale', $locale)
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale));

        $translationUpdated = ArticleTranslation::query()
            ->where('locale', $locale)
            ->whereHas('article', fn ($q) => $q->published()->where('locale', $locale))
            ->max('updated_at');

        return collect([
            (clone $articleQuery)->max('updated_at'),
            (clone $articleQuery)->max('published_at'),
            $translationUpdated,
        ])->filter()->max();
    }
}
