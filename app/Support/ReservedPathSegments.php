<?php

namespace App\Support;

/**
 * Locale altında tek segment olarak kullanılan statik route'lar ve çakışmayı önlemek için
 * Kategori ve makale slug'larında yasaklanan path segmentleri (makale URL öneki dahil).
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
