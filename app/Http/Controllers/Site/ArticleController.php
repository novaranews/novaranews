<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\CategoryTranslation;
use App\Models\Comment;
use App\Models\SeoRedirect;
use App\Services\SiteSeoService;
use App\Support\SafeRedirectUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function show(string $locale, string $articlePrefix, string $articleSlug, SiteSeoService $seoService): View|RedirectResponse
    {
        $translation = ArticleTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $articleSlug)
            ->firstOrFail();

        $article = $translation->article()
            ->with(['category.translations', 'author.profileTranslations', 'translations'])
            ->firstOrFail();

        if ((string) $article->locale !== $locale) {
            abort(404);
        }

        if ($article->status !== 'published' || ! $article->published_at || $article->published_at->isFuture()) {
            abort(404);
        }

        if ($articlePrefix !== article_path_segment($locale, $article->contentTypeKey())) {
            $canonical = $article->publicUrl($locale);
            abort_unless(is_string($canonical), 404);

            return redirect()->to($canonical, 301);
        }

        return $this->renderArticle($article, $translation, $locale, $seoService);
    }

    /**
     * Alternate URL: /{locale}/{categorySlug}/{articleSlug} — same HTML as canonical article URL (200).
     * Canonical in the view points to /{locale}/{article_path_segment}/{slug}.
     */
    public function legacyCategoryArticle(string $locale, string $categorySlug, string $articleSlug): RedirectResponse
    {
        $categoryTranslation = CategoryTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $categorySlug)
            ->first();

        if (! $categoryTranslation) {
            $redirect = $this->findLegacyRedirect($locale, $categorySlug, $articleSlug);
            if ($redirect) {
                return $redirect;
            }
            abort(404);
        }

        $translation = ArticleTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $articleSlug)
            ->whereHas('article', fn ($q) => $q->where('category_id', $categoryTranslation->category_id))
            ->first();

        if (! $translation) {
            $redirect = $this->findLegacyRedirect($locale, $categorySlug, $articleSlug);
            if ($redirect) {
                return $redirect;
            }
            abort(404);
        }

        $article = $translation->article()
            ->with(['category.translations', 'author.profileTranslations', 'translations'])
            ->firstOrFail();

        if ((string) $article->locale !== $locale) {
            abort(404);
        }

        if ($article->status !== 'published' || ! $article->published_at || $article->published_at->isFuture()) {
            abort(404);
        }

        $canonical = $article->publicUrl($locale);
        abort_unless(is_string($canonical), 404);

        return redirect()->to($canonical, 301);
    }

    private function findLegacyRedirect(string $locale, string $categorySlug, string $articleSlug): ?RedirectResponse
    {
        $match = SeoRedirect::query()
            ->where('is_active', true)
            ->where(function ($q) use ($locale) {
                $q->where('locale', $locale)->orWhereNull('locale');
            })
            ->where('from_path', trim($categorySlug.'/'.$articleSlug, '/'))
            ->orderByRaw('locale is null')
            ->first();

        if (! $match) {
            return null;
        }

        $toUrl = SafeRedirectUrl::normalizeInternal((string) $match->to_url);

        return $toUrl ? redirect()->to($toUrl, $match->status_code) : null;
    }

    private function renderArticle(Article $article, ArticleTranslation $translation, string $locale, SiteSeoService $seoService): View
    {
        $relatedLimit = 20;

        $allRelated = Article::query()
            ->published()
            ->forLocale($locale)
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->with(['translations', 'category.translations'])
            ->latest('published_at')
            ->take(80)
            ->get();

        $related = $allRelated->take($relatedLimit);
        if ($related->count() < $relatedLimit) {
            $need = $relatedLimit - $related->count();
            $extra = Article::query()
                ->published()
                ->forLocale($locale)
                ->where('id', '!=', $article->id)
                ->whereNotIn('id', $related->pluck('id')->all())
                ->with(['translations', 'category.translations'])
                ->latest('published_at')
                ->take($need)
                ->get();
            $related = $related->merge($extra)->take($relatedLimit);
        }

        $moreInCategory = $allRelated->slice($relatedLimit)->values();

        $localeSwitchUrls = [];

        // Own URL. Cross-language alternates are separate Article rows grouped by hreflang_group.
        $ownUrl = $article->publicUrl($article->locale);
        if ($ownUrl !== null) {
            $localeSwitchUrls[$article->locale] = $ownUrl;
        }

        // Cross-article hreflang group. This follows the group explicitly set in
        // admin; do not over-filter here or manually grouped language variants
        // disappear from hreflang output.
        if (filled($article->hreflang_group)) {
            $groupArticles = \App\Models\Article::query()
                ->where('hreflang_group', $article->hreflang_group)
                ->where('id', '!=', $article->id)
                ->where('status', 'published')
                ->with('translations')
                ->get();

            foreach ($groupArticles as $groupArticle) {
                $loc = $groupArticle->locale;
                if (isset($localeSwitchUrls[$loc])) {
                    continue;
                }
                $u = $groupArticle->publicUrl($loc);
                if ($u !== null) {
                    $localeSwitchUrls[$loc] = $u;
                }
            }
        }

        // Approved top-level comments with replies eager-loaded.
        $comments = Comment::where('article_id', $article->id)
            ->whereNull('parent_id')
            ->where('approved', true)
            ->with(['replies'])
            ->orderBy('created_at')
            ->get();

        return view('site.article', compact(
            'article',
            'translation',
            'related',
            'moreInCategory',
            'localeSwitchUrls',
            'comments'
        ) + [
            'seo' => $seoService->article($article, $translation, $locale),
        ]);
    }
}
