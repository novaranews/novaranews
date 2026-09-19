<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoRedirect;
use App\Support\SafeRedirectUrl;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoRedirectController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $items = SeoRedirect::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('from_path', 'like', '%'.$q.'%')
                        ->orWhere('to_url', 'like', '%'.$q.'%');
                });
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.redirects.index', compact('items', 'q'));
    }

    public function store(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');
        if ($locale === '') {
            $locale = null;
        }

        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'max:8'],
            'from_path' => ['required', 'string', 'max:500', 'regex:/^[a-z0-9][a-z0-9\-\/]*$/i'],
            'to_url' => [
                'required',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || SafeRedirectUrl::normalizeInternal($value) === null) {
                        $fail('Redirect target must be an internal URL on this site.');
                    }
                },
            ],
            'status_code' => ['required', 'in:301,302'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fromPath = trim((string) $validated['from_path'], '/');
        $toUrl = SafeRedirectUrl::normalizeInternal((string) $validated['to_url']);
        if ($toUrl === null) {
            return back()->withErrors(['to_url' => 'Redirect target must be an internal URL on this site.'])->withInput();
        }

        $redirect = SeoRedirect::query()->updateOrCreate(
            ['locale' => $locale, 'from_path' => $fromPath],
            [
                'to_url' => $toUrl,
                'status_code' => (int) $validated['status_code'],
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        AuditLogger::log('seo_redirect.saved', $redirect, [
            'locale' => $redirect->locale,
            'from_path' => $redirect->from_path,
            'status_code' => $redirect->status_code,
        ]);

        return back()->with('success', 'Redirect saved.');
    }

    public function destroy(SeoRedirect $redirect): RedirectResponse
    {
        AuditLogger::log('seo_redirect.deleted', $redirect, [
            'locale' => $redirect->locale,
            'from_path' => $redirect->from_path,
        ]);
        $redirect->delete();

        return back()->with('success', 'Redirect deleted.');
    }
}
