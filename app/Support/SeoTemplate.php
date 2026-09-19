<?php

namespace App\Support;

use App\Models\Setting;

class SeoTemplate
{
    public static function localeValue(string $keyPrefix, string $locale, string $fallback = ''): string
    {
        $key = $keyPrefix.'_'.$locale;
        $value = Setting::site($key, null);

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return $fallback;
    }

    /** @param array<string, string> $replacements */
    public static function render(string $template, array $replacements = []): string
    {
        return strtr($template, $replacements);
    }

    /** @param array<string, string> $replacements */
    public static function rendered(string $keyPrefix, string $locale, string $fallback, array $replacements = []): string
    {
        return static::render(
            static::localeValue($keyPrefix, $locale, $fallback),
            $replacements
        );
    }
}

