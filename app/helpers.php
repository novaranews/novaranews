<?php

if (! function_exists('page_url')) {
    /**
     * Return the localised URL for a static page.
     *
     * Priority: DB slug (cached 1h) → page key.
     *
     * Usage:  page_url('contact')        → /tr/iletisim  (current locale)
     *         page_url('contact', 'de')  → /de/kontakt
     */
    function page_url(string $page, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        // Try DB slug (cached per locale+page for 1 hour)
        $cacheKey = "page_slug.{$locale}.{$page}";
        $slug = cache()->remember($cacheKey, 3600, function () use ($locale, $page): ?string {
            try {
                return \App\Models\StaticPageTranslation::query()
                    ->whereHas('page', fn ($q) => $q->where('key', $page))
                    ->where('locale', $locale)
                    ->whereNotNull('slug')
                    ->value('slug');
            } catch (\Throwable) {
                return null;
            }
        });

        $slug = $slug ?? $page;

        return url("/{$locale}/{$slug}");
    }
}

if (! function_exists('article_path_segment')) {
    /**
     * Public makale URL’sinin ilk path segmenti: /{locale}/{segment}/{slug}
     * Varies by content type (news, guide, analysis, review, and so on).
     *
     * @param  string|null  $contentType  Article::CONTENT_TYPES biri; null ise news.
     */
    function article_path_segment(?string $locale = null, ?string $contentType = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $contentType = $contentType ?? 'news';
        if (! in_array($contentType, \App\Models\Article::CONTENT_TYPES, true)) {
            $contentType = 'news';
        }
        $byType = config('novaranews.article_path_segment_by_content_type', []);
        $map = is_array($byType[$contentType] ?? null) ? $byType[$contentType] : [];
        if (isset($map[$locale]) && is_string($map[$locale]) && $map[$locale] !== '') {
            return $map[$locale];
        }
        $fallback = config('novaranews.default_locale', 'en');
        if (isset($map[$fallback]) && is_string($map[$fallback]) && $map[$fallback] !== '') {
            return $map[$fallback];
        }

        return match ($contentType) {
            'guide' => 'guide',
            'analysis' => 'analysis',
            'review' => 'review',
            default => 'news',
        };
    }
}

if (! function_exists('category_path_segment')) {
    /**
     * Public kategori listesi URL’sinin ilk path segmenti: /{locale}/{segment}/{category-slug}
     * For example: tr → kategori, en → category, de → kategorie.
     */
    function category_path_segment(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $map = config('novaranews.category_path_segment', []);
        if (isset($map[$locale]) && is_string($map[$locale]) && $map[$locale] !== '') {
            return $map[$locale];
        }
        $fallback = config('novaranews.default_locale', 'en');

        return (string) ($map[$fallback] ?? 'category');
    }
}

if (! function_exists('clear_page_url_cache')) {
    /**
     * Clear cached slugs for a specific page (call after slug update in admin).
     */
    function clear_page_url_cache(string $page): void
    {
        foreach (config('novaranews.locales', ['en']) as $locale) {
            cache()->forget("page_slug.{$locale}.{$page}");
        }
    }
}
