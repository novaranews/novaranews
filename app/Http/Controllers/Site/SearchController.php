<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use App\Services\SiteSeoService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, string $locale, SiteSeoService $seoService)
    {
        $query = trim($request->input('q', ''));
        $articles = collect();
        $perPage = max(6, min(48, (int) Setting::site('articles_per_page', 12)));

        if (mb_strlen($query) >= 2) {
            $escapedQuery = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query);

            $articles = Article::query()
                ->published()
                ->forLocale($locale)
                ->whereHas('translations', function ($q) use ($escapedQuery, $locale) {
                    $q->where('locale', $locale)
                      ->where(function ($q2) use ($escapedQuery) {
                          $q2->where('title', 'like', "%{$escapedQuery}%")
                             ->orWhere('excerpt', 'like', "%{$escapedQuery}%");
                      });
                })
                ->with(['translations', 'category.translations'])
                ->latest('published_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        $canonicalUrl = route('search', ['locale' => $locale, 'q' => $query], true);

        $localeSwitchUrls = collect(config('novaranews.locales'))
            ->mapWithKeys(fn (string $l) => [$l => route('search', ['locale' => $l, 'q' => $query], true)])
            ->all();

        return view('site.search', compact('query', 'articles', 'localeSwitchUrls') + [
            'seo' => $seoService->search($locale, $query, $canonicalUrl),
        ]);
    }
}
