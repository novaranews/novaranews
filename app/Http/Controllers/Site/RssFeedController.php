<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function __invoke(string $locale): Response
    {
        $articles = Article::query()
            ->published()
            ->forLocale($locale)
            ->with(['translations', 'author', 'category.translations'])
            ->latest('published_at')
            ->limit(50)
            ->get();

        $xml = view('site.rss', [
            'locale' => $locale,
            'articles' => $articles,
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
