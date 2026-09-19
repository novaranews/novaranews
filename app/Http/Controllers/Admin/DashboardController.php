<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\LocalizedNewsSitemapController;
use App\Http\Controllers\Site\LocalizedSitemapController;
use App\Models\AiArticleGeneration;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\CategoryTranslation;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\StaticPageTranslation;
use App\Models\User;
use App\Models\UserProfileTranslation;
use App\Services\GoogleNewsReadinessService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(GoogleNewsReadinessService $readinessService): View
    {
        $stats = [
            'articles_total' => Article::query()->count(),
            'articles_live' => Article::query()->published()->count(),
            'articles_scheduled' => Article::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '>', now())
                ->count(),
            'articles_draft' => Article::query()->where('status', 'draft')->count(),
            'articles_pipeline' => Article::query()->whereIn('status', ['editor_reviewed', 'ai_ready'])->count(),
            'articles_seo_missing' => ArticleTranslation::query()
                ->whereHas('article', fn ($q) => $q->published())
                ->where(function ($q) {
                    $q->whereNull('meta_title')
                        ->orWhere('meta_title', '')
                        ->orWhereNull('meta_description')
                        ->orWhere('meta_description', '');
                })
                ->count(),
            'ai_pending' => AiArticleGeneration::query()->where('status', 'pending')->count(),
            'ai_processing' => AiArticleGeneration::query()->where('status', 'processing')->count(),
            'ai_failed' => AiArticleGeneration::query()->where('status', 'failed')->count(),
            'contact_unread' => ContactMessage::query()->whereNull('read_at')->count(),
        ];

        $botEnabled = Setting::get('bot_enabled', true);
        $autoPublish = Setting::get('bot_auto_publish', false);

        // Son 30 günün günlük yayın sayıları (grafik için)
        $chartData = collect(range(29, 0))->map(function (int $daysAgo) {
            $day = now()->subDays($daysAgo)->toDateString();
            $count = \App\Models\Article::query()
                ->where('status', 'published')
                ->whereDate('published_at', $day)
                ->count();
            return ['date' => $day, 'count' => $count];
        })->all();

        $readiness = $readinessService->build();

        $locales = config('novaranews.locales', ['en']);
        $localeCount = max(1, count($locales));
        $defaultLocale = (string) config('novaranews.default_locale', 'en');

        $urlEntryCount = ($localeCount * 2)
            + StaticPageTranslation::query()
                ->whereIn('locale', $locales)
                ->whereHas('page', fn ($q) => $q->whereIn('key', ['about', 'contact', 'privacy', 'cookies', 'editorial-policy']))
                ->count()
            + CategoryTranslation::query()
                ->whereIn('locale', $locales)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->count()
            + (User::query()->whereNotNull('slug')->count() * $localeCount)
            + ArticleTranslation::query()
                ->whereIn('locale', $locales)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('robots_noindex')->orWhere('robots_noindex', false);
                })
                ->whereHas('article', fn ($q) => $q->published())
                ->count();

        // Keep author count aligned with locale-profile filter used in localized sitemap.
        $authorLocales = UserProfileTranslation::query()
            ->whereIn('locale', $locales)
            ->where(function ($q) {
                $q->whereNotNull('title')->where('title', '!=', '')
                    ->orWhereNotNull('bio')->where('bio', '!=', '');
            })
            ->distinct()
            ->count('user_id');
        $urlEntryCount -= (User::query()->whereNotNull('slug')->count() * $localeCount);
        $urlEntryCount += $authorLocales;

        $urlPages = 0;
        $urlExpectedPages = 0;
        foreach ($locales as $locale) {
            foreach (LocalizedSitemapController::TYPES as $type) {
                $segmentPages = LocalizedSitemapController::pagesCount($locale, $type);
                $urlPages += $segmentPages;
                // Sitemap index’te içerik yoksa o tür için dosya yok (0 sayfa).
                $urlExpectedPages += $segmentPages;
            }
        }

        $newsEntryCount = LocalizedNewsSitemapController::recentNewsTranslationCountAllLocales();
        $newsPages = 0;
        $newsExpectedPages = 0;
        foreach ($locales as $locale) {
            $segmentPages = LocalizedNewsSitemapController::pagesCount($locale);
            $newsPages += $segmentPages;
            $newsExpectedPages += $segmentPages;
        }

        $sitemapHealth = [
            'url' => [
                'entries' => $urlEntryCount,
                'per_file' => LocalizedSitemapController::PER_FILE,
                'pages' => $urlPages,
                'expected_pages' => $urlExpectedPages,
                'status' => $urlPages === $urlExpectedPages ? 'pass' : 'fail',
            ],
            'news' => [
                'entries' => $newsEntryCount,
                'per_file' => LocalizedNewsSitemapController::PER_FILE,
                'pages' => $newsPages,
                'expected_pages' => $newsExpectedPages,
                'status' => $newsPages === $newsExpectedPages ? 'pass' : 'fail',
            ],
            'links' => [
                'index' => route('sitemap'),
                'urls_first' => route('sitemap.locale.index', ['locale' => $defaultLocale]),
                'urls_last' => route('sitemap.locale.type', ['locale' => $defaultLocale, 'type' => 'articles']),
                'news_first' => route('news-sitemap.locale', ['locale' => $defaultLocale]),
                'news_last' => route('news-sitemap.locale', ['locale' => $defaultLocale]),
            ],
            'last_seen' => [
                'index' => $this->formatLastSeen(Cache::get('sitemap:last_hit:index')),
                'urls' => $this->formatLastSeen(Cache::get('sitemap:last_hit:urls')),
                'news' => $this->formatLastSeen(Cache::get('sitemap:last_hit:news')),
            ],
            'freshness' => [
                'index' => $this->freshnessState(Cache::get('sitemap:last_hit:index')),
                'urls' => $this->freshnessState(Cache::get('sitemap:last_hit:urls')),
                'news' => $this->freshnessState(Cache::get('sitemap:last_hit:news')),
            ],
            'last_bot' => [
                'index' => $this->identifyBot(Cache::get('sitemap:last_ua:index')),
                'urls'  => $this->identifyBot(Cache::get('sitemap:last_ua:urls')),
                'news'  => $this->identifyBot(Cache::get('sitemap:last_ua:news')),
            ],
        ];

        $opsSnapshot = [
            'pipeline' => $stats['articles_pipeline'],
            'seo_missing' => $stats['articles_seo_missing'],
            'sitemap_status' => in_array('fail', [
                $sitemapHealth['freshness']['index'],
                $sitemapHealth['freshness']['urls'],
                $sitemapHealth['freshness']['news'],
            ], true) ? 'fail' : (in_array('warn', [
                $sitemapHealth['freshness']['index'],
                $sitemapHealth['freshness']['urls'],
                $sitemapHealth['freshness']['news'],
            ], true) ? 'warn' : 'pass'),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'botEnabled',
            'autoPublish',
            'chartData',
            'readiness',
            'sitemapHealth',
            'opsSnapshot'
        ));
    }

    private function identifyBot(mixed $ua): ?string
    {
        if (! is_string($ua) || trim($ua) === '') {
            return null;
        }

        $lower = strtolower($ua);

        $known = [
            'googlebot'        => 'Googlebot',
            'google-inspectiontool' => 'Google Inspection Tool',
            'apis-google'      => 'Google APIs',
            'bingbot'          => 'Bingbot',
            'yandexbot'        => 'YandexBot',
            'duckduckbot'      => 'DuckDuckBot',
            'baiduspider'      => 'Baiduspider',
            'ahrefsbot'        => 'AhrefsBot',
            'semrushbot'       => 'SemrushBot',
            'screaming frog'   => 'Screaming Frog',
            'mj12bot'          => 'Majestic Bot',
            'dotbot'           => 'OpenSiteExplorer',
            'gptbot'           => 'GPTBot (OpenAI)',
            'claudebot'        => 'ClaudeBot (Anthropic)',
            'perplexitybot'    => 'PerplexityBot',
            'anthropic-ai'     => 'Anthropic AI',
            'facebookexternalhit' => 'Facebook',
            'twitterbot'       => 'Twitter/X',
            'linkedinbot'      => 'LinkedIn',
        ];

        foreach ($known as $needle => $label) {
            if (str_contains($lower, $needle)) {
                return $label;
            }
        }

        // Bilinmeyen — UA'nın ilk 60 karakterini göster
        return mb_substr($ua, 0, 60).(mb_strlen($ua) > 60 ? '…' : '');
    }

    private function formatLastSeen(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $dt = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $dt->format('Y-m-d H:i:s').' ('.$dt->diffForHumans().')';
    }

    private function freshnessState(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return 'fail';
        }

        try {
            /** @var CarbonInterface $dt */
            $dt = Carbon::parse($value);
        } catch (\Throwable) {
            return 'fail';
        }

        $mins = $dt->diffInMinutes(now());

        if ($mins <= 120) {
            return 'pass';
        }
        if ($mins <= 1440) {
            return 'warn';
        }

        return 'fail';
    }
}
