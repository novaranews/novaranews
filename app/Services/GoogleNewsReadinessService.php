<?php

namespace App\Services;

use App\Http\Controllers\Site\LocalizedNewsSitemapController;
use App\Models\Article;
use App\Models\Setting;
use App\Support\ArticleContentRules;
use App\Support\ImageDimensions;

class GoogleNewsReadinessService
{
    public function build(): array
    {
        $items = [
            $this->publisherNameItem(),
            $this->publisherLogoItem(),
            $this->authorCompletenessItem(),
            $this->articleSeoHealthItem(),
            $this->articleImageHealthItem(),
            $this->newsSitemapItem(),
        ];

        $score = $this->score($items);
        $status = $score >= 85 ? 'pass' : ($score >= 60 ? 'warn' : 'fail');

        return [
            'score' => $score,
            'status' => $status,
            'items' => $items,
            'counts' => [
                'pass' => count(array_filter($items, fn ($item) => $item['status'] === 'pass')),
                'warn' => count(array_filter($items, fn ($item) => $item['status'] === 'warn')),
                'fail' => count(array_filter($items, fn ($item) => $item['status'] === 'fail')),
            ],
        ];
    }

    private function publisherNameItem(): array
    {
        $value = trim((string) Setting::site('publisher_name', config('app.name')));
        $ok = mb_strlen($value) >= 2;

        return [
            'key' => 'publisher_name',
            'status' => $ok ? 'pass' : 'fail',
            'title' => __('site.admin_readiness_item_publisher_name'),
            'message' => $ok
                ? __('site.admin_readiness_item_publisher_name_ok', ['value' => $value])
                : __('site.admin_readiness_item_publisher_name_fail'),
            'href' => route('admin.settings.index').'?tab=seo',
        ];
    }

    private function publisherLogoItem(): array
    {
        $logoUrl = trim((string) Setting::site('publisher_logo_url', ''));
        if ($logoUrl === '') {
            return [
                'key' => 'publisher_logo',
                'status' => 'fail',
                'title' => __('site.admin_readiness_item_publisher_logo'),
                'message' => __('site.admin_readiness_item_publisher_logo_fail'),
                'href' => route('admin.settings.index').'?tab=seo',
            ];
        }

        $isUrl = filter_var($logoUrl, FILTER_VALIDATE_URL) !== false;
        if (! $isUrl) {
            return [
                'key' => 'publisher_logo',
                'status' => 'warn',
                'title' => __('site.admin_readiness_item_publisher_logo'),
                'message' => __('site.admin_readiness_item_publisher_logo_warn_url'),
                'href' => route('admin.settings.index').'?tab=seo',
            ];
        }

        return [
            'key' => 'publisher_logo',
            'status' => 'pass',
            'title' => __('site.admin_readiness_item_publisher_logo'),
            'message' => __('site.admin_readiness_item_publisher_logo_ok'),
            'href' => route('admin.settings.index').'?tab=seo',
        ];
    }

    private function authorCompletenessItem(): array
    {
        $recentArticles = Article::query()
            ->published()
            ->where('content_type', 'news')
            ->where('published_at', '>=', now()->subHours(48))
            ->with('author')
            ->get();

        if ($recentArticles->isEmpty()) {
            return [
                'key' => 'authors',
                'status' => 'warn',
                'title' => __('site.admin_readiness_item_authors'),
                'message' => __('site.admin_readiness_item_authors_warn_empty'),
                'href' => route('admin.articles.index'),
            ];
        }

        $missingAuthor = 0;
        $weakProfiles = 0;
        foreach ($recentArticles as $article) {
            $author = $article->author;
            if (! $author) {
                $missingAuthor++;
                continue;
            }

            $defaultLoc = config('novaranews.default_locale', 'en');
            $bioWords = $author->profileBioWordCountForDefaultLocale();
            $hasTitle = filled($author->profileTitleForLocale($defaultLoc));
            if (! filled($author->slug) || ! $hasTitle || $bioWords < 40) {
                $weakProfiles++;
            }
        }

        if ($missingAuthor > 0) {
            return [
                'key' => 'authors',
                'status' => 'fail',
                'title' => __('site.admin_readiness_item_authors'),
                'message' => __('site.admin_readiness_item_authors_fail_missing', ['count' => $missingAuthor]),
                'href' => route('admin.articles.index'),
            ];
        }

        if ($weakProfiles > 0) {
            return [
                'key' => 'authors',
                'status' => 'warn',
                'title' => __('site.admin_readiness_item_authors'),
                'message' => __('site.admin_readiness_item_authors_warn_weak', ['count' => $weakProfiles]),
                'href' => route('profile.edit'),
            ];
        }

        return [
            'key' => 'authors',
            'status' => 'pass',
            'title' => __('site.admin_readiness_item_authors'),
            'message' => __('site.admin_readiness_item_authors_ok', ['count' => $recentArticles->count()]),
            'href' => route('admin.articles.index'),
        ];
    }

    private function articleSeoHealthItem(): array
    {
        $translations = \App\Models\ArticleTranslation::query()
            ->whereHas('article', function ($query): void {
                $query->published()
                    ->whereColumn('articles.locale', 'article_translations.locale')
                    ->where('content_type', 'news')
                    ->where('published_at', '>=', now()->subHours(48));
            })
            ->get();

        if ($translations->isEmpty()) {
            return [
                'key' => 'meta',
                'status' => 'warn',
                'title' => __('site.admin_readiness_item_meta'),
                'message' => __('site.admin_readiness_item_meta_warn_empty'),
                'href' => route('admin.articles.index'),
            ];
        }

        $good = 0;
        $hasNoindex = 0;
        foreach ($translations as $translation) {
            $titleLen = mb_strlen(trim((string) ($translation->meta_title ?: $translation->title)));
            $descLen = mb_strlen(trim((string) $translation->meta_description));
            $titleOk = $titleLen >= 35 && $titleLen <= 70;
            $descOk = $descLen >= 110 && $descLen <= 180;
            if ($titleOk && $descOk) {
                $good++;
            }
            if ($translation->robots_noindex) {
                $hasNoindex++;
            }
        }

        if ($hasNoindex > 0) {
            return [
                'key' => 'meta',
                'status' => 'fail',
                'title' => __('site.admin_readiness_item_meta'),
                'message' => __('site.admin_readiness_item_meta_fail_noindex', ['count' => $hasNoindex]),
                'href' => route('admin.articles.index'),
            ];
        }

        $ratio = (int) round(($good / max(1, $translations->count())) * 100);
        $status = $ratio >= 75 ? 'pass' : ($ratio >= 50 ? 'warn' : 'fail');

        return [
            'key' => 'meta',
            'status' => $status,
            'title' => __('site.admin_readiness_item_meta'),
            'message' => __('site.admin_readiness_item_meta_ratio', [
                'good' => $good,
                'total' => $translations->count(),
                'ratio' => $ratio,
            ]),
            'href' => route('admin.articles.index'),
        ];
    }

    private function articleImageHealthItem(): array
    {
        $articles = Article::query()
            ->published()
            ->where('content_type', 'news')
            ->where('published_at', '>=', now()->subHours(48))
            ->get();

        if ($articles->isEmpty()) {
            return [
                'key' => 'images',
                'status' => 'warn',
                'title' => __('site.admin_readiness_item_images'),
                'message' => __('site.admin_readiness_item_images_warn_empty'),
                'href' => route('admin.articles.index'),
            ];
        }

        $bad = 0;
        foreach ($articles as $article) {
            if (! $article->featured_image) {
                $bad++;
                continue;
            }
            $dims = ImageDimensions::forStoragePublic($article->featured_image);
            if (! $dims || $dims['width'] < 1200) {
                $bad++;
            }
        }

        if ($bad === 0) {
            return [
                'key' => 'images',
                'status' => 'pass',
                'title' => __('site.admin_readiness_item_images'),
                'message' => __('site.admin_readiness_item_images_ok', ['count' => $articles->count()]),
                'href' => route('admin.articles.index'),
            ];
        }

        $status = $bad <= max(1, (int) floor($articles->count() * 0.2)) ? 'warn' : 'fail';

        return [
            'key' => 'images',
            'status' => $status,
            'title' => __('site.admin_readiness_item_images'),
            'message' => __('site.admin_readiness_item_images_bad', ['bad' => $bad, 'total' => $articles->count()]),
            'href' => route('admin.articles.index'),
        ];
    }

    private function newsSitemapItem(): array
    {
        $count = LocalizedNewsSitemapController::recentNewsTranslationCountAllLocales();

        $pages = 0;
        foreach (config('novaranews.locales', ['en']) as $locale) {
            $pages += LocalizedNewsSitemapController::pagesCount((string) $locale);
        }
        $defaultLocale = (string) config('novaranews.default_locale', 'en');
        if ($count === 0) {
            return [
                'key' => 'sitemap',
                'status' => 'warn',
                'title' => __('site.admin_readiness_item_sitemap'),
                'message' => __('site.admin_readiness_item_sitemap_warn_empty'),
                'href' => route('news-sitemap.locale', ['locale' => $defaultLocale]),
            ];
        }

        return [
            'key' => 'sitemap',
            'status' => 'pass',
            'title' => __('site.admin_readiness_item_sitemap'),
            'message' => __('site.admin_readiness_item_sitemap_ok', ['count' => $count, 'pages' => $pages]),
            'href' => route('news-sitemap.locale', ['locale' => $defaultLocale]),
        ];
    }

    private function score(array $items): int
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += match ($item['status']) {
                'pass' => 1.0,
                'warn' => 0.5,
                default => 0.0,
            };
        }

        return (int) round(($sum / max(1, count($items))) * 100);
    }
}
