<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use App\Observers\ArticleObserver;
use App\Support\SafeCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Article::observe(ArticleObserver::class);

        TrustProxies::at(config('novaranews.trusted_proxies', '*'));

        RateLimiter::for('login-ip', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(15)->by($request->session()->get('login.two_factor.id', $request->ip()).'|'.$request->ip());
        });

        View::composer('site.*', function ($view) {
            $ttlNav = (int) Setting::site('site_cache_ttl_nav', config('novaranews.cache_ttl_nav', 1800));
            $navCategories = SafeCache::remember(
                'site.nav.categories',
                max(1, $ttlNav),
                fn () => Category::query()->with('translations')->orderBy('sort_order')->get()
            );
            $view->with('navCategories', $navCategories);

            $locale = app()->getLocale();
            $ttlHome = (int) Setting::site('site_cache_ttl_home', config('novaranews.cache_ttl_home', 120));
            $breakingNewsItems = SafeCache::remember(
                'site.breaking.list.'.$locale,
                max(1, $ttlHome),
                function () use ($locale) {
                    $articles = Article::query()
                        ->published()
                        ->forLocale($locale)
                        ->where('is_breaking', true)
                        ->with(['translations', 'category.translations'])
                        ->latest('published_at')
                        ->limit(10)
                        ->get();

                    $items = [];
                    foreach ($articles as $article) {
                        $tr = $article->translations->firstWhere('locale', $locale);
                        if (! $tr || $tr->title === '') {
                            continue;
                        }
                        $url = $article->publicUrl($locale);
                        if (! $url) {
                            continue;
                        }
                        $items[] = ['title' => $tr->title, 'url' => $url];
                    }

                    return $items;
                }
            );
            $view->with('breakingNewsItems', $breakingNewsItems);
        });
    }
}
