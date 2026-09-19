<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\StaticPage;
use App\Models\StaticPageTranslation;
use App\Models\User;
use App\Support\SafeCache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LocalizedSitemapController extends Controller
{
    public const PER_FILE = 2000;
    private const CHUNK_SIZE = 500;
    public const TYPES = ['pages', 'categories', 'articles', 'authors'];

    public function __invoke(string $locale, string $type, ?int $page = 1): Response|RedirectResponse
    {
        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }

        $page = max(1, (int) ($page ?? 1));
        if ($page === 1 && request()->routeIs('sitemap.locale.type.page')) {
            return redirect()->route('sitemap.locale.type', ['locale' => $locale, 'type' => $type], 301);
        }

        // Return 404 when this locale/type has no content at all
        $totalPages = self::pagesCount($locale, $type);
        if ($totalPages === 0 || $page > $totalPages) {
            abort(404);
        }

        $urls = $this->entriesPage($locale, $type, $page);
        $xml = view('site.sitemap', ['urls' => $urls])->render();

        $hit = now()->toIso8601String();
        SafeCache::put("sitemap:last_hit:{$locale}:{$type}", $hit, now()->addDays(14));
        SafeCache::put('sitemap:last_hit:urls', $hit, now()->addDays(14));
        SafeCache::put('sitemap:last_ua:urls', request()->userAgent() ?? '', now()->addDays(14));

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    public static function pagesCount(string $locale, string $type): int
    {
        $count = match ($type) {
            'pages' => self::pagesEntriesCount($locale),
            'categories' => self::categoriesEntriesCount($locale),
            'articles' => self::articlesEntriesCount($locale),
            'authors' => self::authorsEntriesCount($locale),
            default => 0,
        };

        if ($count === 0) {
            return 0;
        }

        return (int) ceil($count / self::PER_FILE);
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    private function entriesPage(string $locale, string $type, int $page): Collection
    {
        $start = ($page - 1) * self::PER_FILE;
        $end = $start + self::PER_FILE;
        $position = 0;
        $entries = collect();

        $push = function (array $entry) use (&$position, $start, $end, $entries): void {
            if ($position >= $start && $position < $end) {
                $entries->push($entry);
            }
            $position++;
        };

        match ($type) {
            'pages' => $this->emitPages($locale, $push),
            'categories' => $this->emitCategories($locale, $push),
            'articles' => $this->emitArticles($locale, $push),
            'authors' => $this->emitAuthors($locale, $push),
            default => null,
        };

        return $entries;
    }

    private static function pagesEntriesCount(string $locale): int
    {
        $staticPages = StaticPageTranslation::query()
            ->where('locale', $locale)
            ->whereHas('page', fn ($q) => $q
                ->where('is_active', true)
                ->whereIn('key', ['about', 'contact', 'privacy', 'cookies', 'editorial-policy']))
            ->count();

        return 2 + $staticPages; // home + authors listing + static pages
    }

    private static function categoriesEntriesCount(string $locale): int
    {
        return CategoryTranslation::query()
            ->where('locale', $locale)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->count();
    }

    private static function articlesEntriesCount(string $locale): int
    {
        return ArticleTranslation::query()
            ->where('locale', $locale)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(function ($q) {
                $q->whereNull('robots_noindex')->orWhere('robots_noindex', false);
            })
            ->whereHas('article', fn ($q) => $q->published()->where('locale', $locale))
            ->count();
    }

    private static function authorsEntriesCount(string $locale): int
    {
        $count = 0;
        User::query()
            ->whereNotNull('slug')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($users) use ($locale, &$count): void {
                foreach ($users as $user) {
                    if (self::hasLocaleProfileData($user, $locale) && $user->profileUrl($locale) !== null) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function emitPages(string $locale, callable $push): void
    {
        $homeLastMod = $this->homeLastModForLocale($locale);
        $push([
            'loc' => route('home', ['locale' => $locale]),
            'lastmod' => $homeLastMod->toAtomString(),
            'changefreq' => 'hourly',
            'priority' => '1.0',
        ]);

        $staticPages = StaticPage::query()
            ->where('is_active', true)
            ->whereIn('key', ['about', 'contact', 'privacy', 'cookies', 'editorial-policy'])
            ->with('translations')
            ->get()
            ->keyBy('key');
        $pageRouteByKey = ['about', 'contact', 'privacy', 'cookies', 'editorial-policy'];
        foreach ($staticPages as $key => $staticPage) {
            if (! in_array($key, $pageRouteByKey, true)) {
                continue;
            }
            $tr = $staticPage->translations->firstWhere('locale', $locale);
            if (! $tr) {
                continue;
            }
            $lm = $staticPage->updated_at->gt($tr->updated_at) ? $staticPage->updated_at : $tr->updated_at;
            $push([
                'loc' => page_url($key, $locale),
                'lastmod' => $lm->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ]);
        }

        $push([
            'loc' => route('authors.index', ['locale' => $locale]),
            'lastmod' => $homeLastMod->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '0.4',
        ]);
    }

    private function emitCategories(string $locale, callable $push): void
    {
        $categoryArticleMax = Article::query()
            ->published()
            ->where('locale', $locale)
            ->selectRaw('category_id, MAX(updated_at) as mx, MAX(published_at) as published_mx')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $categories = Category::query()
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->get();

        foreach ($categories as $category) {
            $tr = $category->translations->first();
            if (! $tr || ! is_string($tr->slug) || trim($tr->slug) === '') {
                continue;
            }
            $row = $categoryArticleMax->get($category->id);
            $articleMx = $row ? collect([
                $row->mx ? Carbon::parse($row->mx) : null,
                $row->published_mx ? Carbon::parse($row->published_mx) : null,
            ])->filter()->sortByDesc(fn (Carbon $c) => $c->getTimestamp())->first() : null;
            $lm = $articleMx
                ? $category->updated_at->max($articleMx)
                : $category->updated_at;
            $push([
                'loc' => route('category.show', [
                    'locale' => $locale,
                    'categoryPrefix' => category_path_segment($locale),
                    'slug' => $tr->slug,
                ]),
                'lastmod' => $lm->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => '0.8',
            ]);
        }
    }

    private function emitArticles(string $locale, callable $push): void
    {
        Article::query()
            ->published()
            ->where('locale', $locale)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderByDesc('updated_at')
            ->chunk(self::CHUNK_SIZE, function ($articles) use ($locale, $push): void {
                foreach ($articles as $article) {
                    $tr = $article->translations->first();
                    if (! $tr) {
                        continue;
                    }
                    if ((bool) ($tr->robots_noindex ?? false)) {
                        continue;
                    }
                    if (! is_string($tr->slug) || trim($tr->slug) === '') {
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
                        $entry['image_loc'] = url(Storage::url($article->featured_image));
                        $entry['image_title'] = $article->featured_image_alt ?: ($tr->title ?? '');
                    }
                    $push($entry);
                }
            });
    }

    private function emitAuthors(string $locale, callable $push): void
    {
        $authorArticleMax = Article::query()
            ->published()
            ->where('locale', $locale)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(updated_at) as mx, MAX(published_at) as published_mx')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        User::query()
            ->whereNotNull('slug')
            ->with('profileTranslations')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($authors) use ($locale, $authorArticleMax, $push): void {
                foreach ($authors as $user) {
                    if (! self::hasLocaleProfileData($user, $locale)) {
                        continue;
                    }
                    $loc = $user->profileUrl($locale);
                    if (! $loc) {
                        continue;
                    }
                    $row = $authorArticleMax->get($user->id);
                    $articleMx = $row ? collect([
                        $row->mx ? Carbon::parse($row->mx) : null,
                        $row->published_mx ? Carbon::parse($row->published_mx) : null,
                    ])->filter()->sortByDesc(fn (Carbon $c) => $c->getTimestamp())->first() : null;
                    $lm = $articleMx
                        ? $user->updated_at->max($articleMx)
                        : $user->updated_at;
                    $push([
                        'loc' => $loc,
                        'lastmod' => $lm->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.5',
                    ]);
                }
            });
    }

    private static function hasLocaleProfileData(User $user, string $locale): bool
    {
        $title = trim((string) ($user->profileTitleForLocale($locale) ?? ''));
        $bio = trim(strip_tags((string) ($user->profileBioForLocale($locale) ?? '')));

        return $title !== '' || $bio !== '';
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
