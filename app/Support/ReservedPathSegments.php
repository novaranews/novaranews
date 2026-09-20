<?php

namespace App\Support;

/**
 * Static routes that use a single segment below a locale, plus path segments reserved
 * from category and article slugs to prevent collisions (including article URL prefixes).
 */
final class ReservedPathSegments
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $articlePrefixes = [];
        foreach (config('novaranews.article_path_segment_by_content_type', []) as $localeMap) {
            foreach (array_values((array) $localeMap) as $s) {
                if (is_string($s) && $s !== '') {
                    $articlePrefixes[] = $s;
                }
            }
        }
        $articlePrefixes = array_values(array_unique($articlePrefixes));

        $categoryPrefixes = array_values(array_filter(
            array_values(config('novaranews.category_path_segment', [])),
            static fn ($s) => is_string($s) && $s !== ''
        ));

        return array_values(array_unique(array_merge(
            [
                'about',
                'contact',
                'privacy',
                'cookies',
                'editorial-policy',
                'authors',
                'author',
                'p',
                'search',
                'feed.xml',
            ],
            $articlePrefixes,
            $categoryPrefixes,
            config('novaranews.locales', ['en', 'tr']),
        )));
    }
}
