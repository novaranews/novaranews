<?php

namespace App\Support;

final class SiteCache
{
    public static function forgetHomeAndNav(): void
    {
        foreach (config('novaranews.locales', []) as $loc) {
            SafeCache::forget('site.home.data.'.$loc);
            SafeCache::forget('site.breaking.'.$loc);
            SafeCache::forget('site.breaking.list.'.$loc);
        }
        SafeCache::forget('site.nav.categories');
    }
}
