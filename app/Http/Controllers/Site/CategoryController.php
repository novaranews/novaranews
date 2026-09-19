<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CategoryTranslation;
use App\Models\SeoRedirect;
use App\Models\Setting;
use App\Services\SiteSeoService;
use App\Support\SafeRedirectUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(string $locale, string $categoryPrefix, string $slug, SiteSeoService $seoService): View|RedirectResponse
    {
        $translation = CategoryTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        if (! $translation) {
            $redirect = SeoRedirect::query()
                ->where('is_active', true)
                ->where(function ($q) use ($locale) {
                    $q->where('locale', $locale)->orWhereNull('locale');
                })
                ->where('from_path', trim($categoryPrefix.'/'.$slug, '/'))
                ->orderByRaw('locale is null')
                ->first();
            if ($redirect) {
                $toUrl = SafeRedirectUrl::normalizeInternal((string) $redirect->to_url);
                if ($toUrl) {
                    return redirect()->to($toUrl, $redirect->status_code);
                }
            }
            abort(404);
        }

        if ($categoryPrefix !== category_path_segment($locale)) {
            return redirect()->route('category.show', [
                'locale' => $locale,
                'categoryPrefix' => category_path_segment($locale),
                'slug' => $slug,
            ], 301);
        }

        $category = $translation->category()->with('translations')->firstOrFail();
        $perPage  = max(6, min(48, (int) Setting::site('articles_per_page', 12)));
        $page     = request()->integer('page', 1);

        // Redirect ?page=1 to the canonical base URL (no query param).
        // This eliminates the duplicate ?page=1 URL and fixes pagination sequence errors.
        if ($page === 1 && request()->has('page')) {
            return redirect()->route('category.show', [
                'locale' => $locale,
                'categoryPrefix' => category_path_segment($locale),
                'slug' => $slug,
            ], 301);
        }

        $baseQuery = Article::query()
            ->published()
            ->forLocale($locale)
            ->where('category_id', $category->id)
            ->with(['translations', 'author'])
            ->latest('published_at');

        // On page 1, pull the featured (latest) article separately so it does not
        // consume a grid slot — which would leave the last row of the 3-column grid
        // one card short (e.g. 12 paginated → 1 featured + 11 grid = incomplete row).
        $featuredArticle = $page === 1 ? (clone $baseQuery)->first() : null;

        // Exclude the featured article from the paginated grid query.
        $articles = (clone $baseQuery)
            ->when($featuredArticle !== null, fn ($q) => $q->where('id', '!=', $featuredArticle->id))
            ->paginate($perPage)
            ->withQueryString();

        $localeSwitchUrls = collect(config('novaranews.locales'))
            ->mapWithKeys(function (string $l) use ($category) {
                $tr = $category->translations->firstWhere('locale', $l);

                return $tr ? [$l => route('category.show', ['locale' => $l, 'categoryPrefix' => category_path_segment($l), 'slug' => $tr->slug])] : [];
            })
            ->all();

        return view('site.category', compact('category', 'articles', 'translation', 'localeSwitchUrls', 'featuredArticle') + [
            'seo' => $seoService->category($locale, $category, $translation),
        ]);
    }
}
