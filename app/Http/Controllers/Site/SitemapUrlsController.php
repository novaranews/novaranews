<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\StaticPage;
use App\Models\StaticPageTranslation;
use App\Models\User;
use App\Support\SafeCache;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SitemapUrlsController extends Controller
{
    public const PER_FILE = 2000;
    private const CHUNK_SIZE = 500;

    public function __invoke(?int $page = 1): Response
    {
        $page = max(1, (int) ($page ?? 1));
        $start = ($page - 1) * self::PER_FILE;
        $end = $start + self::PER_FILE;
        $locales = config('novaranews.locales', ['en']);

        $categoryArticleMax = Article::query()
            ->published()
            ->selectRaw('category_id, locale, MAX(updated_at) as mx, MAX(published_at) as published_mx')
            ->groupBy('category_id', 'locale')
            ->get()
            ->keyBy(fn ($r) => $r->category_id.'|'.$r->locale);

        $staticPages = StaticPage::query()
            ->whereIn('key', ['about', 'contact', 'privacy', 'cookies', 'editorial-policy'])
            ->with('translations')
            ->get()
            ->keyBy('key');

        $pageRouteByKey = [
            'about',
            'contact',
            'privacy',
            'cookies',
            'editorial-policy',
        ];

        $position = 0;
        $pageUrls = collect();
        $pushUrl = function (array $row) use (&$position, $start, $end, $pageUrls): void {
            if ($position >= $start && $position < $end) {
                $pageUrls->push($row);
            }
            $position++;
        };

        foreach ($locales as $locale) {
            $homeLastMod = $this->homeLastModForLocale($locale);
            $pushUrl([
                'loc' => route('home', ['locale' => $locale]),
                'lastmod' => $homeLastMod->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ]);

            foreach ($staticPages as $key => $staticPage) {
                if (! in_array($key, $pageRouteByKey, true)) {
                    continue;
                }
                $tr = $staticPage->translations->firstWhere('locale', $locale);
                if (! $tr) {
                    continue;
                }
                $lm = $staticPage->updated_at->gt($tr->updated_at) ? $staticPage->updated_at : $tr->updated_at;
                $pushUrl([
                    'loc' => page_url($key, $locale),
                    'lastmod' => $lm->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.3',
                ]);
            }

            $pushUrl([
                'loc' => route('authors.index', ['locale' => $locale]),
                'lastmod' => $homeLastMod->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.4',
            ]);
        }

        $categories = Category::query()->with('translations')->get();

        $authorArticleMax = Article::query()
            ->published()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, locale, MAX(updated_at) as mx, MAX(published_at) as published_mx')
            ->groupBy('user_id', 'locale')
            ->get()
            ->keyBy(fn ($r) => $r->user_id.'|'.$r->locale);
        foreach ($categories as $category) {
            foreach ($locales as $locale) {
                $tr = $category->translations->firstWhere('locale', $locale);
                if (! $tr) {
                    continue;
                }
                $row = $categoryArticleMax->get($category->id.'|'.$locale);
                $articleMx = $row ? collect([
                    $row->mx ? Carbon::parse($row->mx) : null,
                    $row->published_mx ? Carbon::parse($row->published_mx) : null,
                ])->filter()->sortByDesc(fn (Carbon $c) => $c->getTimestamp())->first() : null;
                $lm = $articleMx
                    ? $category->updated_at->max($articleMx)
                    : $category->updated_at;
                $pushUrl([
                    'loc' => route('category.show', ['locale' => $locale, 'categoryPrefix' => category_path_segment($locale), 'slug' => $tr->slug]),
                    'lastmod' => $lm->toAtomString(),
                    'changefreq' => 'hourly',
                    'priority' => '0.8',
                ]);
            }
        }

        Article::query()
            ->published()
            ->with(['translations'])
            ->orderByDesc('updated_at')
            ->chunk(self::CHUNK_SIZE, function ($articles) use ($locales, $pushUrl): void {
                foreach ($articles as $article) {
                    foreach ($locales as $locale) {
                        $tr = $article->translations->firstWhere('locale', $locale);
                        if (! $tr) {
                            continue;
                        }
                        if ((bool) ($tr->robots_noindex ?? false)) {
                            continue;
                        }
                        $locUrl = $article->publicUrl($locale);
                        if (! $locUrl) {
                            continue;
                        }
                        $entry = [
                            'loc' => $locUrl,
                            'lastmod' => $article->publicModifiedAt($tr)->toAtomString(),
                            'changefreq' => 'daily',
                            'priority' => '0.9',
                        ];
                        if ($article->featured_image) {
                            $entry['image_loc']   = url(Storage::url($article->featured_image));
                            $entry['image_title'] = $article->featured_image_alt ?: ($tr->title ?? '');
                        }
                        $pushUrl($entry);
                    }
                }
            });

        User::query()
            ->whereNotNull('slug')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($authors) use ($locales, $authorArticleMax, $pushUrl): void {
                foreach ($authors as $user) {
                    foreach ($locales as $locale) {
                        $loc = $user->profileUrl($locale);
                        if (! $loc) {
                            continue;
                        }
                        $row = $authorArticleMax->get($user->id.'|'.$locale);
                        $articleMx = $row ? collect([
                            $row->mx ? Carbon::parse($row->mx) : null,
                            $row->published_mx ? Carbon::parse($row->published_mx) : null,
                        ])->filter()->sortByDesc(fn (Carbon $c) => $c->getTimestamp())->first() : null;
                        $lm = $articleMx
                            ? $user->updated_at->max($articleMx)
                            : $user->updated_at;
                        $pushUrl([
                            'loc' => $loc,
                            'lastmod' => $lm->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.5',
                        ]);
                    }
                }
            });

        $xml = view('site.sitemap', ['urls' => $pageUrls])->render();
        SafeCache::put('sitemap:last_hit:urls', now()->toIso8601String(), now()->addDays(14));

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    public static function pagesCount(): int
    {
        $locales = config('novaranews.locales', ['en']);
        $localeCount = max(1, count($locales));

        $staticPagesCount = StaticPageTranslation::query()
            ->whereIn('locale', $locales)
            ->whereHas('page', fn ($q) => $q->whereIn('key', ['about', 'contact', 'privacy', 'cookies', 'editorial-policy']))
            ->count();

        $categoryCount = CategoryTranslation::query()
            ->whereIn('locale', $locales)
            ->count();

        $authorCount = User::query()->whereNotNull('slug')->count() * $localeCount;
        $articleCount = \App\Models\ArticleTranslation::query()
            ->whereIn('locale', $locales)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(function ($q) {
                $q->whereNull('robots_noindex')->orWhere('robots_noindex', false);
            })
            ->whereHas('article', fn ($q) => $q->published())
            ->count();
        $baseCount = $localeCount * 2; // home + authors index

        $total = $baseCount + $staticPagesCount + $categoryCount + $authorCount + $articleCount;

        return max(1, (int) ceil($total / self::PER_FILE));
    }

    private function homeLastModForLocale(string $locale): Carbon
    {
        $articleMax = Article::query()->published()->forLocale($locale)->max('updated_at');
        $articlePublishedMax = Article::query()->published()->forLocale($locale)->max('published_at');
        $staticMax = StaticPageTranslation::query()->where('locale', $locale)->max('updated_at');
        $categoryMax = Category::query()->max('updated_at');

        $dates = array_filter([
            $articleMax ? Carbon::parse($articleMax) : null,
            $articlePublishedMax ? Carbon::parse($articlePublishedMax) : null,
            $staticMax ? Carbon::parse($staticMax) : null,
            $categoryMax ? Carbon::parse($categoryMax) : null,
        ]);

        if ($dates === []) {
            return now();
        }

        return collect($dates)->sortByDesc(fn (Carbon $c) => $c->getTimestamp())->first();
    }
}
