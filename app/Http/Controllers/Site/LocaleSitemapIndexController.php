<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\SafeCache;
use Illuminate\Http\Response;

class LocaleSitemapIndexController extends Controller
{
    public function __invoke(string $locale): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $now = now()->toAtomString();
        $items = [];

        foreach (\App\Http\Controllers\Site\LocalizedSitemapController::TYPES as $type) {
            $pages = \App\Http\Controllers\Site\LocalizedSitemapController::pagesCount($locale, $type);
            $lastmod = SafeCache::get("sitemap:last_hit:{$locale}:{$type}", $now);
            $lastmod = is_string($lastmod) && trim($lastmod) !== '' ? $lastmod : $now;

            for ($i = 1; $i <= $pages; $i++) {
                $loc = $i === 1
                    ? "{$base}/sitemap-{$locale}-{$type}.xml"
                    : "{$base}/sitemap-{$locale}-{$type}-{$i}.xml";
                $items[] = "  <sitemap>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </sitemap>";
            }
        }

        $newsPages = \App\Http\Controllers\Site\LocalizedNewsSitemapController::pagesCount($locale);
        $newsLastmod = SafeCache::get("sitemap:last_hit:{$locale}:news", $now);
        $newsLastmod = is_string($newsLastmod) && trim($newsLastmod) !== '' ? $newsLastmod : $now;
        for ($i = 1; $i <= $newsPages; $i++) {
            $loc = $i === 1
                ? "{$base}/news-sitemap-{$locale}.xml"
                : "{$base}/news-sitemap-{$locale}-{$i}.xml";
            $items[] = "  <sitemap>\n    <loc>{$loc}</loc>\n    <lastmod>{$newsLastmod}</lastmod>\n  </sitemap>";
        }

        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode("\n", $items)
            . "\n</sitemapindex>\n";

        SafeCache::put("sitemap:last_hit:locale:{$locale}", now()->toIso8601String(), now()->addDays(14));

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }
}
