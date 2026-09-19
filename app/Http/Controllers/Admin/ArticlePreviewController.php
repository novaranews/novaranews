<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SiteSeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticlePreviewController extends Controller
{
    public function __invoke(Request $request, Article $article, SiteSeoService $seoService): View
    {
        $locale = (string) $request->query('locale', config('novaranews.default_locale', 'en'));
        if (! in_array($locale, config('novaranews.locales', ['en']), true)) {
            $locale = config('novaranews.default_locale', 'en');
        }

        $article->load(['category.translations', 'author', 'translations']);
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
            'article' => $article,
            'translation' => $translation,
            'related' => $related,
            'moreInCategory' => $moreInCategory,
            'localeSwitchUrls' => $localeSwitchUrls,
            'comments' => collect(),
            'preview' => true,
            'previewMode' => 'admin',
            'seo' => $seoService->article($article, $translation, $locale, true),
        ]);
    }
}
