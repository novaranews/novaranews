<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use App\Services\SiteSeoService;
use App\Support\SafeCache;

class HomeController extends Controller
{
    public function __invoke(SiteSeoService $seoService)
    {
        $locale = app()->getLocale();
        $ttl = (int) Setting::site('site_cache_ttl_home', config('novaranews.cache_ttl_home', 120));

        $data = SafeCache::remember(
            'site.home.data.'.$locale,
            max(1, $ttl),
            function () {
                $locale = app()->getLocale();

                $hero = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations', 'author'])
                    ->latest('published_at')
                    ->first();

                $side = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations', 'author'])
                    ->when($hero, fn ($q) => $q->where('id', '!=', $hero->id))
                    ->latest('published_at')
                    ->take(3)
                    ->get();

                $heroSideIds = collect([$hero?->id])
                    ->merge($side->pluck('id'))
                    ->filter()->unique()->values()->all();

                $latest = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations', 'author'])
                    ->whereNotIn('id', $heroSideIds)
                    ->latest('published_at')
                    ->take(9)
                    ->get();

                $usedIds = collect($heroSideIds)
                    ->merge($latest->pluck('id'))
                    ->filter()->unique()->values()->all();

                // Gündem: "Son haberler" gridindeki kayıtlar hariç, en son güncellenenler (sekme farkı için ayrı küme)
                $trending = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations', 'author'])
                    ->whereNotIn('id', $usedIds)
                    ->latest('updated_at')
                    ->take(9)
                    ->get();

                $usedAfterTrending = collect($usedIds)
                    ->merge($trending->pluck('id'))
                    ->filter()->unique()->values()->all();

                // En çok okunan: gerçek okuma sayımı yok; breaking + yenilik önceliği, üstteki iki sekmeden farklı küme
                $mostRead = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations', 'author'])
                    ->whereNotIn('id', $usedAfterTrending)
                    ->orderByDesc('is_breaking')
                    ->latest('published_at')
                    ->take(9)
                    ->get();

                // Sidebar: ilk 5 latest
                $sidebarTrending = $latest->take(5);

                // Editörün Seçimi: admin panelde işaretlenen yayınlar (en fazla 5)
                $editorsPicks = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations'])
                    ->where('is_editors_pick', true)
                    ->latest('published_at')
                    ->take(5)
                    ->get();

                $categorySections = \App\Models\Category::query()
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($cat) {
                        $articles = Article::query()
                            ->published()
                            ->forLocale(app()->getLocale())
                            ->where('category_id', $cat->id)
                            ->with(['translations', 'category.translations', 'author'])
                            ->latest('published_at')
                            ->take(6)
                            ->get();

                        return [
                            'category' => $cat,
                            'articles' => $articles,
                        ];
                    });

                // Son ~24 saatte yayınlananlar — yatay şerit (okuma sayısı yok; güncellik + son dakika önceliği)
                $hotToday = Article::query()
                    ->published()
                    ->forLocale($locale)
                    ->with(['translations', 'category.translations', 'author'])
                    ->where('published_at', '>=', now()->subDay())
                    ->orderByDesc('is_breaking')
                    ->latest('published_at')
                    ->limit(12)
                    ->get();

                $aiCategory = \App\Models\Category::query()->where('key', 'artificial-intelligence')->first();
                $aiSpotlight = collect();
                if ($aiCategory) {
                    $aiSpotlight = Article::query()
                        ->published()
                        ->forLocale($locale)
                        ->where('category_id', $aiCategory->id)
                        ->with(['translations', 'category.translations', 'author'])
                        ->latest('published_at')
                        ->limit(12)
                        ->get();
                }

                return compact(
                    'hero', 'side', 'latest', 'trending', 'mostRead',
                    'sidebarTrending', 'editorsPicks', 'categorySections', 'hotToday',
                    'aiCategory', 'aiSpotlight'
                );
            }
        );

        $localeSwitchUrls = collect(config('novaranews.locales'))
            ->mapWithKeys(fn (string $l) => [$l => route('home', ['locale' => $l])])
            ->all();

        return view('site.home', [
            ...$data,
            'localeSwitchUrls' => $localeSwitchUrls,
            'seo' => $seoService->home($locale),
        ]);
    }
}
