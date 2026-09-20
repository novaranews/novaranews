<?php

use App\Http\Controllers\Admin\AiGenerationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\ArticleGoogleIndexingController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\ArticlePreviewController as AdminArticlePreviewController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Site\CommentController as SiteCommentController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\GovernanceController as AdminGovernanceController;
use App\Http\Controllers\Admin\StaticPageController as AdminStaticPageController;
use App\Http\Controllers\Site\ArticleController as SiteArticleController;
use App\Http\Controllers\Site\ArticlePublicPreviewController;
use App\Http\Controllers\Site\AuthorController;
use App\Http\Controllers\Site\CategoryController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LocaleSitemapIndexController;
use App\Http\Controllers\Site\LocalizedNewsSitemapController;
use App\Http\Controllers\Site\LocalizedSitemapController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\RssFeedController;
use App\Http\Controllers\Site\RobotsTxtController;
use App\Http\Controllers\Site\RedirectController as SiteRedirectController;
use App\Http\Controllers\Site\SitemapIndexController;
use App\Http\Controllers\Site\SitemapUrlsController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\Admin\SeoRedirectController as AdminSeoRedirectController;
use App\Support\SafeRedirectUrl;
use App\Http\Controllers\Profile\TwoFactorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

$localesPattern = implode('|', config('novaranews.locales', ['en']));
$pathSlugPattern = '[a-z0-9]+(?:-[a-z0-9]+)*';
$articlePathSegments = [];
foreach (config('novaranews.article_path_segment_by_content_type', []) as $localeMap) {
    foreach ((array) $localeMap as $seg) {
        if (is_string($seg) && $seg !== '') {
            $articlePathSegments[] = $seg;
        }
    }
}
$articlePathSegments = array_values(array_unique($articlePathSegments));
$articlePrefixPattern = $articlePathSegments !== [] ? implode('|', $articlePathSegments) : 'news';

$categoryPathSegments = array_values(array_unique(array_filter(
    array_values(config('novaranews.category_path_segment', [])),
    static fn ($s) => is_string($s) && $s !== ''
)));
$categoryPrefixPattern = $categoryPathSegments !== [] ? implode('|', $categoryPathSegments) : 'category';

// ── MCP server: JSON-RPC endpoint for AI agents ──────────────────────────────
Route::post('/mcp', [McpController::class, 'handle'])->middleware(['web', 'throttle:60,1'])->name('mcp');
Route::get('/robots.txt', RobotsTxtController::class)->name('robots');

// ── CSRF token refresh for forms rendered from cached pages ──────────────────
// FastCGI and Cloudflare never cache it because it uses POST.
// Excluded from CSRF validation in bootstrap/app.php.
Route::post('/novara-csrf', function () {
    return response()->json(['token' => csrf_token()])
        ->header('Cache-Control', 'no-store, private');
})->middleware(['web'])->name('novara.csrf');

Route::get('/', function () {
    $locales = config('novaranews.locales', ['en']);
    $default = config('novaranews.default_locale', 'en');
    $cookieLocale = request()->cookie('site_locale');
    $preferred = request()->getPreferredLanguage($locales);

    $targetLocale = $default;
    if (is_string($cookieLocale) && in_array($cookieLocale, $locales, true)) {
        $targetLocale = $cookieLocale;
    } elseif (is_string($preferred) && in_array($preferred, $locales, true)) {
        $targetLocale = $preferred;
    }

    // 301 when redirecting to default locale (Googlebot always lands here),
    // 302 when locale differs from default (user preference / cookie).
    $code = ($targetLocale === $default) ? 301 : 302;

    return redirect('/'.$targetLocale, $code);
});
Route::get('/sitemap.xml', SitemapIndexController::class)->name('sitemap');
Route::get('/sitemap-{locale}.xml', LocaleSitemapIndexController::class)
    ->where(['locale' => $localesPattern])
    ->name('sitemap.locale.index');
Route::get('/sitemap-{locale}-{type}.xml', LocalizedSitemapController::class)
    ->where(['locale' => $localesPattern, 'type' => 'pages|categories|articles|authors'])
    ->name('sitemap.locale.type');
Route::get('/sitemap-{locale}-{type}-1.xml', function (string $locale, string $type) {
    return redirect()->route('sitemap.locale.type', ['locale' => $locale, 'type' => $type], 301);
})->where(['locale' => $localesPattern, 'type' => 'pages|categories|articles|authors'])->name('sitemap.locale.type.page1');
Route::get('/sitemap-{locale}-{type}-{page}.xml', LocalizedSitemapController::class)
    ->where(['locale' => $localesPattern, 'type' => 'pages|categories|articles|authors'])
    ->whereNumber('page')
    ->name('sitemap.locale.type.page');
Route::get('/news-sitemap-{locale}.xml', LocalizedNewsSitemapController::class)
    ->where(['locale' => $localesPattern])
    ->name('news-sitemap.locale');
Route::get('/news-sitemap-{locale}-1.xml', function (string $locale) {
    return redirect()->route('news-sitemap.locale', ['locale' => $locale], 301);
})->where(['locale' => $localesPattern])->name('news-sitemap.locale.page1');
Route::get('/news-sitemap-{locale}-{page}.xml', LocalizedNewsSitemapController::class)
    ->where(['locale' => $localesPattern])
    ->whereNumber('page')
    ->name('news-sitemap.locale.page');

// Legacy aliases (kept for backward compatibility during migration)
Route::get('/sitemap-urls.xml', function () {
    return redirect()->route('sitemap', [], 301);
})->name('sitemap.urls');
Route::get('/sitemap-urls-{page}.xml', function (int $page) {
    if ($page === 1) {
        return redirect()->route('sitemap.urls', [], 301);
    }
    return redirect()->route('sitemap', [], 301);
})->whereNumber('page')->name('sitemap.urls.page');
Route::get('/news-sitemap.xml', function () {
    return redirect()->route('news-sitemap.locale', ['locale' => config('novaranews.default_locale', 'en')], 301);
})->name('news-sitemap');
Route::get('/news-sitemap-{page}.xml', function (int $page) {
    $locale = config('novaranews.default_locale', 'en');
    if ($page === 1) {
        return redirect()->route('news-sitemap.locale', ['locale' => $locale], 301);
    }
    return redirect()->route('news-sitemap.locale.page', ['locale' => $locale, 'page' => $page], 301);
})->whereNumber('page')->name('news-sitemap.page');
Route::get('/google-news.xml', function () {
    return redirect()->route('news-sitemap.locale', ['locale' => config('novaranews.default_locale', 'en')], 301);
})->name('google-news-sitemap');

Route::prefix('{locale}')
    ->where(['locale' => $localesPattern])
    ->middleware(['locale'])
    ->group(function () use ($pathSlugPattern, $articlePrefixPattern, $categoryPrefixPattern) {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/search', SearchController::class)->middleware('throttle:30,1')->name('search');
        Route::get('/feed.xml', RssFeedController::class)
            ->middleware('throttle:60,1')
            ->name('feed');
        Route::get('/{categoryPrefix}/{slug}', [CategoryController::class, 'show'])
            ->where(['categoryPrefix' => $categoryPrefixPattern, 'slug' => $pathSlugPattern])
            ->name('category.show');
        Route::get('/{articlePrefix}/{articleSlug}', [SiteArticleController::class, 'show'])
            ->where(['articlePrefix' => $articlePrefixPattern, 'articleSlug' => $pathSlugPattern])
            ->name('article.show');
        Route::get('/p/{article}', ArticlePublicPreviewController::class)
            ->name('article.preview')
            ->middleware('signed');

        Route::post('/comments/{article}', [SiteCommentController::class, 'store'])
            ->name('comments.store')
            ->middleware('throttle:10,1');

        Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
        Route::get('/author/{slug}', [AuthorController::class, 'show'])->name('author.show');

        // Canonical English slugs (named routes used by route() helper)
        Route::get('/about',   [PageController::class,   'about'])->name('page.about');
        Route::get('/contact', [ContactController::class,'show'])->name('page.contact');
        Route::post('/contact',[ContactController::class,'store'])->name('page.contact.store')->middleware('throttle:10,1');
        Route::get('/privacy', [PageController::class,   'privacy'])->name('page.privacy');
        Route::get('/cookies', [PageController::class,   'cookies'])->name('page.cookies');
        Route::get('/editorial-policy', [PageController::class, 'editorialPolicy'])->name('page.editorial_policy');

        // Dynamic localised slug resolver — reads slug from DB (set in admin Static Pages)
        // Must come AFTER all specific named routes to avoid conflicts.
        Route::get('/{pageSlug}', function (string $locale, string $pageSlug) {
            $translation = \App\Models\StaticPageTranslation::with('page')
                ->where('locale', $locale)
                ->where('slug', $pageSlug)
                ->whereHas('page', fn ($q) => $q->where('is_active', true))
                ->first();
            if (! $translation) {
                $redirect = \App\Models\SeoRedirect::query()
                    ->where('is_active', true)
                    ->where(function ($q) use ($locale) {
                        $q->where('locale', $locale)->orWhereNull('locale');
                    })
                    ->where('from_path', trim($pageSlug, '/'))
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
            return match ($translation->page->key) {
                'about'   => app()->call([app(PageController::class), 'about'], ['locale' => $locale]),
                'contact' => app()->call([app(ContactController::class), 'show'], ['locale' => $locale]),
                'privacy' => app()->call([app(PageController::class), 'privacy'], ['locale' => $locale]),
                'cookies' => app()->call([app(PageController::class), 'cookies'], ['locale' => $locale]),
                'editorial-policy' => app()->call([app(PageController::class), 'editorialPolicy'], ['locale' => $locale]),
                default   => abort(404),
            };
        })->where('pageSlug', '[a-z][a-z0-9-]*')->name('page.slug');

        Route::post('/{pageSlug}', function (string $locale, string $pageSlug) {
            $translation = \App\Models\StaticPageTranslation::with('page')
                ->where('locale', $locale)
                ->where('slug', $pageSlug)
                ->whereHas('page', fn ($q) => $q->where('key', 'contact')->where('is_active', true))
                ->first();
            abort_unless($translation, 404);
            return app(ContactController::class)->store(request(), $locale);
        })->where('pageSlug', '[a-z][a-z0-9-]*')->middleware('throttle:10,1')->name('page.slug.post');

        Route::get('/{categorySlug}/{articleSlug}', [SiteArticleController::class, 'legacyCategoryArticle'])
            ->where(['categorySlug' => $pathSlugPattern, 'articleSlug' => $pathSlugPattern]);
        Route::get('/{path}', SiteRedirectController::class)->where('path', '.*');
    });

Route::middleware(['auth', 'admin', 'admin.locale'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('articles/import-example.json', function () {
        $path = base_path('docs/import-articles.example.json');
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    })->name('articles.import-example');
    Route::post('articles/import-json', [AdminArticleController::class, 'importJson'])->name('articles.import-json');
    Route::post('articles/import-json-paste', [AdminArticleController::class, 'importJsonPaste'])->name('articles.import-json-paste');
    Route::get('articles/export-json', [AdminArticleController::class, 'exportJson'])->name('articles.export-json');
    Route::post('articles/bulk-status', [AdminArticleController::class, 'bulkStatus'])->name('articles.bulk-status');
    Route::post('articles/bulk-destroy', [AdminArticleController::class, 'bulkDestroy'])->name('articles.bulk-destroy');
    Route::get('articles/{article}/preview', AdminArticlePreviewController::class)->name('articles.preview');
    Route::post('articles/{article}/google-indexing', ArticleGoogleIndexingController::class)
        ->middleware('throttle:60,1')
        ->name('articles.google-indexing');
    Route::resource('articles', AdminArticleController::class)->except(['show']);
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::get('static-pages', [AdminStaticPageController::class, 'index'])->name('static-pages.index');
    Route::get('static-pages/{staticPage}/edit', [AdminStaticPageController::class, 'edit'])->name('static-pages.edit');
    Route::put('static-pages/{staticPage}', [AdminStaticPageController::class, 'update'])->name('static-pages.update');
    Route::delete('static-pages/{staticPage}', [AdminStaticPageController::class, 'destroy'])->name('static-pages.destroy');
    Route::patch('static-pages/{staticPage}/restore', [AdminStaticPageController::class, 'restore'])->name('static-pages.restore');
    Route::get('media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::post('media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::post('media/editor-upload', [AdminMediaController::class, 'storeForEditor'])->name('media.editor-upload');
    Route::post('media/optimize-all', [AdminMediaController::class, 'optimizeAll'])->name('media.optimize-all');
    Route::delete('media', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    Route::get('redirects', [AdminSeoRedirectController::class, 'index'])->name('redirects.index');
    Route::post('redirects', [AdminSeoRedirectController::class, 'store'])->name('redirects.store');
    Route::delete('redirects/{redirect}', [AdminSeoRedirectController::class, 'destroy'])->name('redirects.destroy');
    Route::get('governance/revisions', [AdminGovernanceController::class, 'revisions'])->name('governance.revisions');
    Route::get('governance/audits', [AdminGovernanceController::class, 'audits'])->name('governance.audits');
    Route::delete('governance/revisions/{revision}', [AdminGovernanceController::class, 'destroyRevision'])->name('governance.revisions.destroy');
    Route::delete('governance/revisions', [AdminGovernanceController::class, 'clearRevisions'])->name('governance.revisions.clear');
    Route::delete('governance/audits/{audit}', [AdminGovernanceController::class, 'destroyAudit'])->name('governance.audits.destroy');
    Route::delete('governance/audits', [AdminGovernanceController::class, 'clearAudits'])->name('governance.audits.clear');

    // Comments
    Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
    Route::patch('comments/{comment}/reject', [AdminCommentController::class, 'reject'])->name('comments.reject');
    Route::delete('comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');

    // Contact Messages
    Route::get('contact-messages', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('contact-messages/{contactMessage}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::patch('contact-messages/{contactMessage}/read', [AdminContactMessageController::class, 'markRead'])->name('contact-messages.read');
    Route::patch('contact-messages/{contactMessage}/unread', [AdminContactMessageController::class, 'markUnread'])->name('contact-messages.unread');
    Route::delete('contact-messages/{contactMessage}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    // AI News Bot
    Route::prefix('ai-generations')->name('ai-generations.')->group(function () {
        Route::get('/', [AiGenerationController::class, 'index'])->name('index');
        Route::post('/run', [AiGenerationController::class, 'runNow'])->middleware('throttle:10,1')->name('run');
        Route::post('/manual', [AiGenerationController::class, 'manualGenerate'])->middleware('throttle:20,1')->name('manual');
        Route::delete('/bulk', [AiGenerationController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/clear-status', [AiGenerationController::class, 'clearByStatus'])->name('clear-status');
        Route::get('/sources', [AiGenerationController::class, 'sources'])->name('sources');
        Route::post('/sources', [AiGenerationController::class, 'sourceStore'])->name('source-store');
        Route::post('/sources/check-all', [AiGenerationController::class, 'sourceCheckAll'])->name('source-check-all');
        Route::delete('/sources/bulk', [AiGenerationController::class, 'sourceBulkDestroy'])->name('source-bulk-destroy');
        Route::patch('/sources/{newsSource}/toggle', [AiGenerationController::class, 'sourceToggle'])->name('source-toggle');
        Route::delete('/sources/{newsSource}', [AiGenerationController::class, 'sourceDestroy'])->name('source-destroy');
        Route::get('/{aiGeneration}', [AiGenerationController::class, 'show'])->name('show');
        Route::post('/{aiGeneration}/approve', [AiGenerationController::class, 'approve'])->name('approve');
        Route::post('/{aiGeneration}/retry', [AiGenerationController::class, 'retry'])->name('retry');
        Route::delete('/{aiGeneration}', [AiGenerationController::class, 'destroy'])->name('destroy');
    });
});

Route::get('/dashboard', function () {
    if (request()->user()->is_admin) {
        return app()->call([app(AdminDashboardController::class), '__invoke']);
    }

    return redirect()->route('home', ['locale' => config('novaranews.default_locale', 'en')]);
})->middleware(['auth', 'verified', 'admin.locale'])->name('dashboard');

Route::middleware(['auth', 'admin.locale'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/profile/two-factor', [TwoFactorController::class, 'start'])->name('profile.two-factor.start');
    Route::post('/profile/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('profile.two-factor.confirm');
    Route::post('/profile/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('profile.two-factor.cancel');
    Route::delete('/profile/two-factor', [TwoFactorController::class, 'destroy'])->name('profile.two-factor.destroy');
});

require __DIR__.'/auth.php';
