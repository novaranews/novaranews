<?php

namespace App\Support;

/**
 * Normalizes text that may already contain HTML entities (e.g. from DB/admin)
 * before Blade's {{ }} escapes it again — prevents "Foo &amp;amp; Bar" in &lt;title&gt; and meta.
 *
 * Note: In "View Source", a single & in visible text or attribute values still appears as &amp;
 * after escaping — that is valid HTML5 and renders as "&" on screen. Only &amp;amp; (or the word
 * "&amp;" shown to users) indicates double-encoding worth fixing.
 */
final class HtmlText
{
    public static function decodeEntitiesForBlade(string $value): string
    {
        $current = trim($value);
        for ($i = 0; $i < 12; $i++) {
            $next = html_entity_decode($current, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $current) {
                break;
            }
            $current = $next;
        }

        return $current;
    }
}
