<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SiteSeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request, string $locale, SiteSeoService $seoService): View
    {
        $authors = User::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->with('profileTranslations')
            ->orderBy('name')
            ->get();

        $localeSwitchUrls = [];
        foreach (config('novaranews.locales', ['en']) as $loc) {
            $localeSwitchUrls[$loc] = route('authors.index', ['locale' => $loc]);
        }

        return view('site.authors', compact('authors', 'localeSwitchUrls') + [
            'seo' => $seoService->authors($locale, route('authors.index', ['locale' => $locale])),
        ]);
    }

    public function show(Request $request, string $locale, string $slug, SiteSeoService $seoService): View|RedirectResponse
    {
        $author = User::query()
            ->where('slug', $slug)
            ->with('profileTranslations')
            ->firstOrFail();
        $perPage = max(6, min(48, (int) Setting::site('articles_per_page', 12)));

        // Redirect ?page=1 to the canonical base author URL (no query param).
        if ($request->integer('page', 1) === 1 && $request->has('page')) {
            return redirect()->route('author.show', ['locale' => $locale, 'slug' => $slug], 301);
        }

        $articles = $author->articles()
            ->with(['category.translations', 'translations'])
            ->where('locale', $locale)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate($perPage);

        // Build locale switch URLs (same author, different locale prefix)
        $localeSwitchUrls = [];
        foreach (config('novaranews.locales', ['en']) as $loc) {
            $localeSwitchUrls[$loc] = route('author.show', ['locale' => $loc, 'slug' => $slug]);
        }

        // Pass request()->fullUrl() so paginated pages are self-canonical (includes ?page=X).
        // This prevents "Canonicals: Non-Indexable" where page=2+ canonicalized to base URL.
        return view('site.author', compact('author', 'articles', 'localeSwitchUrls') + [
            'seo' => $seoService->author($locale, $author, request()->fullUrl()),
        ]);
    }
}
