<?php

namespace App\Support;

/**
 * Word counts and editorial rules for article body / author bios (UTF-8 safe).
 */
final class ArticleContentRules
{
    public static function wordCount(?string $text): int
    {
        $text = trim(strip_tags((string) $text));
        if ($text === '') {
            return 0;
        }
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? count($parts) : 0;
    }
}
