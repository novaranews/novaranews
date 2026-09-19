<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $base    = rtrim((string) config('app.url'), '/');
        $locales = config('novaranews.locales', ['en']);

        // Build search disallow rules for each locale (e.g. /en/search, /tr/search).
        // Internal search pages are low-quality and should not be indexed.
        $searchDisallows = array_map(
            static fn (string $l) => 'Disallow: /'.$l.'/search',
            $locales
        );

        $lines = array_merge(
            ['User-agent: *'],
            $searchDisallows,
            [
                'Disallow: /admin',
                'Disallow: /dashboard',
                'Disallow: /login',
                'Disallow: /forgot-password',
                'Disallow: /reset-password',
                'Disallow: /verify-email',
                'Disallow: /confirm-password',
                'Disallow: /two-factor',
                'Disallow: /profile',
                '',
                'Sitemap: '.$base.'/sitemap.xml',
            ]
        );

        foreach ($locales as $locale) {
            $lines[] = 'Sitemap: '.$base.'/sitemap-'.$locale.'-articles.xml';
            // News sitemaps are 48-hour windows — only list if content exists to avoid 404 references
            if (\App\Http\Controllers\Site\LocalizedNewsSitemapController::pagesCount($locale) > 0) {
                $lines[] = 'Sitemap: '.$base.'/news-sitemap-'.$locale.'.xml';
            }
        }

        $lines[] = '';
        $body = implode("\n", $lines);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
