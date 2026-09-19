<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SiteSeoService;
use App\Support\StaticPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    private function localeSwitchUrls(string $pageKey): array
    {
        return collect(config('novaranews.locales'))
            ->mapWithKeys(fn (string $l) => [$l => page_url($pageKey, $l)])
            ->all();
    }

    /**
     * Return 404 when the English hardcoded route is accessed for a locale
     * that has its own slug in the database.
     *
     * Problem: routes/web.php registers /cookies, /about etc. for ALL locales, so
     * /es/cookies is a valid route even though the DB slug is /es/politica-cookies.
     * This creates duplicate content. The fix: if the current URL doesn't match
     * the locale's canonical slug, abort with 404 — only the DB slug URL should work.
     *
     * Example:
     *   /es/politica-cookies → page_url('cookies','es') = /es/politica-cookies → match → OK
     *   /es/cookies          → page_url('cookies','es') = /es/politica-cookies → no match → 404
     *   /en/cookies          → page_url('cookies','en') = /en/cookies          → match → OK
     */
    private function abortIfNotLocalizedUrl(string $pageKey): void
    {
        $canonical = rtrim(page_url($pageKey, app()->getLocale()), '/');
        $current   = rtrim(request()->url(), '/');

        if ($current !== $canonical) {
            abort(404);
        }
    }

    /**
     * Ensure all target="_blank" links in static page HTML have rel="noopener noreferrer".
     * Fixes "Security: Unsafe Cross-Origin Links" — content stored in DB may lack this attribute.
     */
    private function sanitizeExternalLinks(string $html): string
    {
        return (string) preg_replace_callback(
            '/<a\b([^>]*)>/i',
            static function (array $m): string {
                $attrs = $m[1];

                // Only touch links that open in a new tab.
                if (! preg_match('/\btarget\s*=\s*["\']_blank["\']/i', $attrs)) {
                    return '<a'.$attrs.'>';
                }

                // Strip any existing rel attribute, then add the safe value.
                $attrs = (string) preg_replace('/\s+rel\s*=\s*["\'][^"\']*["\']/', '', $attrs);

                return '<a'.$attrs.' rel="noopener noreferrer">';
            },
            $html
        ) ?: $html;
    }

    public function about(string $locale, SiteSeoService $seoService): View
    {
        $this->abortIfNotLocalizedUrl('about');
        abort_unless(StaticPageContent::isActive('about'), 404);
        $content = StaticPageContent::resolve('about', $locale);

        return view('site.pages.about', [
            'localeSwitchUrls' => $this->localeSwitchUrls('about'),
            'seo' => $seoService->staticPage($content),
            'pageContent' => $this->sanitizeExternalLinks($content['html']),
            'pageHeading' => $content['heading'],
        ]);
    }

    public function privacy(string $locale, SiteSeoService $seoService): View
    {
        $this->abortIfNotLocalizedUrl('privacy');
        abort_unless(StaticPageContent::isActive('privacy'), 404);
        $content = StaticPageContent::resolve('privacy', $locale);

        return view('site.pages.privacy', [
            'localeSwitchUrls' => $this->localeSwitchUrls('privacy'),
            'seo' => $seoService->staticPage($content),
            'pageContent' => $this->sanitizeExternalLinks($content['html']),
            'pageHeading' => $content['heading'],
        ]);
    }

    public function cookies(string $locale, SiteSeoService $seoService): View
    {
        $this->abortIfNotLocalizedUrl('cookies');
        abort_unless(StaticPageContent::isActive('cookies'), 404);
        $content = StaticPageContent::resolve('cookies', $locale);

        return view('site.pages.cookies', [
            'localeSwitchUrls' => $this->localeSwitchUrls('cookies'),
            'seo' => $seoService->staticPage($content),
            'pageContent' => $this->sanitizeExternalLinks($content['html']),
            'pageHeading' => $content['heading'],
        ]);
    }

    public function editorialPolicy(string $locale, SiteSeoService $seoService): View
    {
        $this->abortIfNotLocalizedUrl('editorial-policy');
        abort_unless(StaticPageContent::isActive('editorial-policy'), 404);
        $content = StaticPageContent::resolve('editorial-policy', $locale);

        return view('site.pages.editorial-policy', [
            'localeSwitchUrls' => $this->localeSwitchUrls('editorial-policy'),
            'seo' => $seoService->staticPage($content),
            'pageContent' => $this->sanitizeExternalLinks($content['html']),
            'pageHeading' => $content['heading'],
        ]);
    }
}
