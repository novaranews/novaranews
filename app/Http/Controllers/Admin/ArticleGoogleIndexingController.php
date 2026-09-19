<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\GoogleIndexingApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleGoogleIndexingController extends Controller
{
    public function __invoke(Request $request, Article $article, GoogleIndexingApiService $indexing): RedirectResponse
    {
        if (! $indexing->isFeatureEnabled()) {
            return redirect()
                ->route('admin.articles.index')
                ->with('error', __('site.admin_google_index_disabled'));
        }

        if (! $indexing->isConfigured()) {
            return redirect()
                ->route('admin.articles.index')
                ->with('error', __('site.admin_google_index_not_configured'));
        }

        if (! $article->googleIndexingEligible()) {
            return redirect()
                ->route('admin.articles.index')
                ->with('error', __('site.admin_google_index_ineligible'));
        }

        if (! $article->googleIndexingNeedsNotify()) {
            return redirect()
                ->route('admin.articles.index')
                ->with('success', __('site.admin_google_index_already_current'));
        }

        if ($indexing->dailyRemaining() <= 0) {
            return redirect()
                ->route('admin.articles.index')
                ->with('error', __('site.admin_google_index_limit', ['limit' => $indexing->dailyLimit()]));
        }

        $url = $article->publicUrl($article->locale);
        if ($url === null || $url === '' || $url === '#') {
            return redirect()
                ->route('admin.articles.index')
                ->with('error', __('site.admin_google_index_ineligible'));
        }

        $result = $indexing->publishUrlUpdated($url);

        if (! $result['ok']) {
            if ($result['message'] === 'daily_limit') {
                return redirect()
                    ->route('admin.articles.index')
                    ->with('error', __('site.admin_google_index_limit', ['limit' => $indexing->dailyLimit()]));
            }
            if ($result['message'] === 'not_configured') {
                return redirect()
                    ->route('admin.articles.index')
                    ->with('error', __('site.admin_google_index_not_configured'));
            }

            return redirect()
                ->route('admin.articles.index')
                ->with('error', __('site.admin_google_index_fail', ['message' => $result['message']]));
        }

        DB::table('articles')->where('id', $article->id)->update([
            'google_indexing_notified_at' => now(),
        ]);

        return redirect()
            ->route('admin.articles.index')
            ->with('success', __('site.admin_google_index_success'));
    }
}
