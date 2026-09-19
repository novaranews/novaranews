<?php

namespace App\Support;

final class SafeRedirectUrl
{
    public static function normalizeInternal(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $urlHost = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($appHost === '' || $urlHost === '' || $appHost !== $urlHost) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== '' && ! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }
}
