<?php

namespace App\Support;

class SeoMetaVariant
{
    public const DEFAULT = 'default';
    public const LISTING = 'listing';
    public const ARTICLE = 'article';
    public const PROFILE = 'profile';

    public static function normalize(?string $variant): string
    {
        return match ($variant) {
            self::LISTING, self::ARTICLE, self::PROFILE => $variant,
            default => self::DEFAULT,
        };
    }

    /** @return array<string, bool|string|null> */
    public static function preset(?string $variant, ?string $canonical = null): array
    {
        $resolved = self::normalize($variant);
        $openGraphUrl = ($canonical !== null && $canonical !== '') ? $canonical : null;

        return match ($resolved) {
            self::LISTING => [
                'includeDescription' => true,
                'includeRobots' => false,
                'includeOpenGraph' => true,
                'includeTwitter' => true,
                'openGraphType' => 'website',
                'openGraphUrl' => $openGraphUrl,
                'twitterCard' => 'summary_large_image',
            ],
            self::ARTICLE => [
                'includeDescription' => true,
                'includeRobots' => true,
                'includeOpenGraph' => true,
                'includeTwitter' => true,
                'openGraphType' => 'article',
                'openGraphUrl' => $openGraphUrl,
                'twitterCard' => 'summary_large_image',
            ],
            self::PROFILE => [
                'includeDescription' => true,
                'includeRobots' => false,
                'includeOpenGraph' => false,
                'includeTwitter' => false,
                'openGraphType' => null,
                'openGraphUrl' => null,
                'twitterCard' => 'summary',
            ],
            default => [
                'includeDescription' => true,
                'includeRobots' => true,
                'includeOpenGraph' => true,
                'includeTwitter' => true,
                'openGraphType' => 'website',
                'openGraphUrl' => $openGraphUrl,
                'twitterCard' => 'summary_large_image',
            ],
        };
    }
}

