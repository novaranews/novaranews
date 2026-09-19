<?php

declare(strict_types=1);

/**
 * One-off: compare translation keys across locales. Run: php scripts/compare-lang-keys.php
 */
function flattenKeys(array $arr, string $prefix = ''): array
{
    $keys = [];
    foreach ($arr as $k => $v) {
        $path = $prefix === '' ? (string) $k : "{$prefix}.{$k}";
        if (is_array($v)) {
            if (array_is_list($v)) {
                foreach ($v as $i => $item) {
                    $ip = "{$path}.{$i}";
                    if (is_array($item) && ! array_is_list($item)) {
                        $keys = array_merge($keys, flattenKeys($item, $ip));
                    } else {
                        $keys[] = $ip;
                    }
                }
            } else {
                $keys = array_merge($keys, flattenKeys($v, $path));
            }
        } else {
            $keys[] = $path;
        }
    }

    return $keys;
}

$root = dirname(__DIR__);
$locales = ['en', 'tr', 'de', 'fr', 'es'];
$files = ['site.php', 'pages.php'];

foreach ($files as $f) {
    echo "=== {$f} ===\n";
    $sets = [];
    foreach ($locales as $loc) {
        $path = "{$root}/lang/{$loc}/{$f}";
        if (! is_file($path)) {
            echo "MISSING FILE: {$path}\n";
            continue;
        }
        $data = include $path;
        $sets[$loc] = array_flip(flattenKeys($data));
    }
    $base = 'en';
    foreach ($locales as $loc) {
        if ($loc === $base) {
            continue;
        }
        $missing = array_diff_key($sets[$base], $sets[$loc] ?? []);
        $extra = array_diff_key($sets[$loc] ?? [], $sets[$base]);
        if ($missing) {
            echo "{$loc} MISSING vs {$base}: ".implode(', ', array_keys($missing))."\n";
        }
        if ($extra) {
            echo "{$loc} EXTRA vs {$base}: ".implode(', ', array_keys($extra))."\n";
        }
    }
    echo "\n";
}
