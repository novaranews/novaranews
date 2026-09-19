<?php

namespace App\Support;

class ArticleSources
{
    /**
     * @return array<int, array{source_id: ?string, title: string, url: ?string, claim_note: ?string}>
     */
    public static function normalize(mixed $sources): array
    {
        if (! is_array($sources)) {
            return [];
        }

        $normalized = [];
        foreach ($sources as $source) {
            if (is_string($source)) {
                $title = trim($source);
                $url = PublicUrl::isHttpOrHttps($title) ? $title : null;
                if ($url !== null) {
                    $title = (string) parse_url($url, PHP_URL_HOST);
                }
                if ($title !== '') {
                    $normalized[] = [
                        'source_id' => null,
                        'title' => $title,
                        'url' => $url,
                        'claim_note' => null,
                    ];
                }
                continue;
            }

            if (! is_array($source)) {
                continue;
            }

            $url = trim((string) ($source['url'] ?? $source['href'] ?? ''));
            $url = PublicUrl::isHttpOrHttps($url) ? $url : null;

            $title = trim((string) ($source['title'] ?? $source['label'] ?? $source['name'] ?? ''));
            if ($title === '' && $url !== null) {
                $title = (string) parse_url($url, PHP_URL_HOST);
            }

            if ($title === '' && $url === null) {
                continue;
            }

            $normalized[] = [
                'source_id' => filled($source['source_id'] ?? null) ? (string) $source['source_id'] : null,
                'title' => $title !== '' ? $title : $url,
                'url' => $url,
                'claim_note' => filled($source['claim_note'] ?? null) ? (string) $source['claim_note'] : null,
            ];
        }

        return array_values($normalized);
    }

    /**
     * @return array<int, string>
     */
    public static function urls(mixed $sources): array
    {
        return collect(self::normalize($sources))
            ->pluck('url')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
