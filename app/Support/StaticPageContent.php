<?php

namespace App\Support;

use App\Models\StaticPage;

class StaticPageContent
{
    public static function isActive(string $key): bool
    {
        $page = StaticPage::query()
            ->select(['id', 'is_active'])
            ->where('key', $key)
            ->first();

        if (! $page) {
            return true;
        }

        return (bool) $page->is_active;
    }

    /**
     * @return array{meta_title: string, meta_description: string, og_title: string, og_description: string, canonical_url: string, og_image_url: ?string, robots_noindex: bool, robots_nofollow: bool, html: string, heading: string}
     */
    public static function resolve(string $key, string $locale): array
    {
        $fallbackMeta = (string) __("pages.$key.meta_description", [], $locale);
        $fallbackParagraphs = __("pages.$key.paragraphs", [], $locale);
        $fallbackParagraphs = is_array($fallbackParagraphs) ? $fallbackParagraphs : [];
        $defaultLocale = config('novaranews.default_locale', 'en');

        $headingSiteKey = self::headingSiteKey($key);
        $fallbackHeading = (string) __("site.$headingSiteKey", [], $locale);
        $fallbackMetaTitle = $fallbackHeading . ' - ' . config('app.name');

        $page = StaticPage::query()
            ->with('translations')
            ->where('key', $key)
            ->first();

        $fallbackHtml = implode("\n", array_map(
            static fn ($p) => '<p>' . e($p) . '</p>',
            $fallbackParagraphs
        ));

        if (! $page) {
            return [
                'meta_title' => $fallbackMetaTitle,
                'meta_description' => $fallbackMeta,
                'og_title' => $fallbackMetaTitle,
                'og_description' => $fallbackMeta,
                'canonical_url' => page_url($key, $locale),
                'og_image_url' => null,
                'robots_noindex' => false,
                'robots_nofollow' => false,
                'html' => $fallbackHtml,
                'heading' => $fallbackHeading,
            ];
        }

        $translation = $page->translations->firstWhere('locale', $locale)
            ?: $page->translations->firstWhere('locale', $defaultLocale);

        if (! $translation) {
            return [
                'meta_title' => $fallbackMetaTitle,
                'meta_description' => $fallbackMeta,
                'og_title' => $fallbackMetaTitle,
                'og_description' => $fallbackMeta,
                'canonical_url' => page_url($key, $locale),
                'og_image_url' => null,
                'robots_noindex' => false,
                'robots_nofollow' => false,
                'html' => $fallbackHtml,
                'heading' => $fallbackHeading,
            ];
        }

        $meta = filled($translation->meta_description) ? $translation->meta_description : $fallbackMeta;
        $rawContent = trim((string) ($translation->content ?? ''));
        $html = $rawContent !== '' ? $rawContent : $fallbackHtml;

        $heading = filled($translation->title ?? null)
            ? (string) $translation->title
            : $fallbackHeading;
        $metaTitle = filled($translation->meta_title ?? null)
            ? (string) $translation->meta_title
            : $fallbackMetaTitle;
        $ogTitle = filled($translation->og_title ?? null)
            ? (string) $translation->og_title
            : $metaTitle;
        $ogDescription = filled($translation->og_description ?? null)
            ? (string) $translation->og_description
            : $meta;
        $canonicalUrl = filled($translation->canonical_url ?? null)
            ? (string) $translation->canonical_url
            : page_url($key, $locale);
        $ogImageUrl = filled($translation->og_image_url ?? null) ? (string) $translation->og_image_url : null;

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $meta,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'canonical_url' => $canonicalUrl,
            'og_image_url' => $ogImageUrl,
            'robots_noindex' => (bool) ($translation->robots_noindex ?? false),
            'robots_nofollow' => (bool) ($translation->robots_nofollow ?? false),
            'html' => $html,
            'heading' => $heading,
        ];
    }

    /**
     * site.php key for the public H1 / document title fallback.
     */
    public static function headingSiteKey(string $pageKey): string
    {
        return match ($pageKey) {
            'editorial-policy' => 'editorial_policy',
            default => $pageKey,
        };
    }
}
