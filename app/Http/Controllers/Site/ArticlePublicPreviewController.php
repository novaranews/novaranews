<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SiteSeoService;
use Illuminate\View\View;

class ArticlePublicPreviewController extends Controller
{
    public function __invoke(string $locale, Article $article, SiteSeoService $seoService): View
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $article->load(['category.translations', 'author.profileTranslations', 'translations']);

        if ((string) $article->locale !== $locale) {
            abort(404);
        }

        $translation = $article->translations->firstWhere('locale', $locale);
        if (! $translation) {
            abort(404);
        }

        $localeSwitchUrls = [];
        foreach (config('novaranews.locales', ['en']) as $l) {
            $u = $article->publicUrl($l);
            if ($u !== null) {
                $localeSwitchUrls[$l] = $u;
            }
        }

        $related = collect();
        $moreInCategory = collect();

        return view('site.article', [
            'article'          => $article,
            'translation'      => $translation,
            'related'          => $related,
            'moreInCategory'   => $moreInCategory,
            'localeSwitchUrls' => $localeSwitchUrls,
            'comments'         => collect(),
            'seo'              => $seoService->article($article, $translation, $locale),
            'preview'          => true,
            'previewMode'      => 'signed',
        ]);
    }
}
