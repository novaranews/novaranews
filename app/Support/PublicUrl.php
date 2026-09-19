<?php

namespace App\Support;

/**
 * Validates URLs that may be rendered as public links (sources, citations).
 */
final class PublicUrl
{
    public static function isHttpOrHttps(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower((string) $parts['scheme']);

        return in_array($scheme, ['http', 'https'], true);
    }
}
