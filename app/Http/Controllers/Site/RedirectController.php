<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\SeoRedirect;
use App\Support\SafeRedirectUrl;
use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function __invoke(string $locale, string $path): RedirectResponse
    {
        $normalized = trim($path, '/');

        $match = SeoRedirect::query()
            ->where('is_active', true)
            ->where(function ($query) use ($locale) {
                $query->where('locale', $locale)->orWhereNull('locale');
            })
            ->where('from_path', $normalized)
            ->orderByRaw('locale is null')
            ->firstOrFail();

        $toUrl = SafeRedirectUrl::normalizeInternal((string) $match->to_url);
        abort_if($toUrl === null, 404);

        return redirect()->to($toUrl, $match->status_code);
    }
}
