<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use App\Models\StaticPageTranslation;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $editablePageKeys = ['about', 'privacy', 'cookies', 'editorial-policy', 'contact'];

    public function index(): View
    {
        $this->ensureDefaultPages();

        $pages = StaticPage::query()
            ->whereIn('key', $this->editablePageKeys)
            ->orderByRaw("CASE `key` WHEN 'about' THEN 1 WHEN 'privacy' THEN 2 WHEN 'cookies' THEN 3 WHEN 'editorial-policy' THEN 4 WHEN 'contact' THEN 5 ELSE 99 END")
            ->get();

        return view('admin.static-pages.index', compact('pages'));
    }

    public function edit(StaticPage $staticPage): View
    {
        abort_unless(in_array($staticPage->key, $this->editablePageKeys, true), 404);

        $locales      = config('novaranews.locales', ['en']);
        $translations = $staticPage->translations()->get()->keyBy('locale');

        return view('admin.static-pages.edit', compact('staticPage', 'locales', 'translations'));
    }

    public function update(Request $request, StaticPage $staticPage): RedirectResponse
    {
        abort_unless(in_array($staticPage->key, $this->editablePageKeys, true), 404);

        $locales = config('novaranews.locales', ['en']);
        $translationRowIds = [];
        foreach ($locales as $loc) {
            $translationRowIds[$loc] = StaticPageTranslation::query()
                ->where('static_page_id', $staticPage->id)
                ->where('locale', $loc)
                ->value('id');
        }

        $rules = [];
        foreach ($locales as $locale) {
            $rules["translations.$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$locale.meta_title"] = ['nullable', 'string', 'max:255'];
            $slugUnique = Rule::unique('static_page_translations', 'slug')
                ->where(fn ($q) => $q->where('locale', $locale));
            if ($translationRowIds[$locale] !== null) {
                $slugUnique = $slugUnique->ignore($translationRowIds[$locale]);
            }
            $rules["translations.$locale.slug"] = [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slugUnique,
            ];
            $rules["translations.$locale.meta_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$locale.og_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$locale.og_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$locale.canonical_url"] = ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'];
            $rules["translations.$locale.og_image_url"] = ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'];
            $rules["translations.$locale.robots_noindex"] = ['nullable', 'boolean'];
            $rules["translations.$locale.robots_nofollow"] = ['nullable', 'boolean'];
            $rules["translations.$locale.content"] = ['nullable', 'string'];
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $locales, $staticPage): void {
            foreach ($locales as $locale) {
                $data = $validated['translations'][$locale] ?? [];
                StaticPageTranslation::query()->updateOrCreate(
                    ['static_page_id' => $staticPage->id, 'locale' => $locale],
                    [
                        'title' => filled($data['title'] ?? null) ? $data['title'] : null,
                        'meta_title' => filled($data['meta_title'] ?? null) ? $data['meta_title'] : null,
                        'slug' => filled($data['slug'] ?? null) ? $data['slug'] : null,
                        'meta_description' => $data['meta_description'] ?? null,
                        'og_title' => filled($data['og_title'] ?? null) ? $data['og_title'] : null,
                        'og_description' => $data['og_description'] ?? null,
                        'canonical_url' => filled($data['canonical_url'] ?? null) ? $data['canonical_url'] : null,
                        'og_image_url' => filled($data['og_image_url'] ?? null) ? $data['og_image_url'] : null,
                        'robots_noindex' => (bool) ($data['robots_noindex'] ?? false),
                        'robots_nofollow' => (bool) ($data['robots_nofollow'] ?? false),
                        'content' => $data['content'] ?? null,
                        'admin_locked_at' => now(),
                    ]
                );
            }
            AuditLogger::log('static_page.updated', $staticPage, ['key' => $staticPage->key]);
        });

        clear_page_url_cache($staticPage->key);

        return redirect()->route('admin.static-pages.index')->with('success', __('site.admin_static_page_updated'));
    }

    public function destroy(StaticPage $staticPage): RedirectResponse
    {
        abort_unless(in_array($staticPage->key, $this->editablePageKeys, true), 404);

        $staticPage->forceFill(['is_active' => false])->save();
        AuditLogger::log('static_page.disabled', $staticPage, ['key' => $staticPage->key]);
        clear_page_url_cache($staticPage->key);

        return redirect()->route('admin.static-pages.index')->with('success', __('site.admin_static_page_deleted'));
    }

    public function restore(StaticPage $staticPage): RedirectResponse
    {
        abort_unless(in_array($staticPage->key, $this->editablePageKeys, true), 404);

        $staticPage->forceFill(['is_active' => true])->save();
        AuditLogger::log('static_page.restored', $staticPage, ['key' => $staticPage->key]);
        clear_page_url_cache($staticPage->key);

        return redirect()->route('admin.static-pages.index')->with('success', __('site.admin_static_page_restored'));
    }

    private function ensureDefaultPages(): void
    {
        foreach ($this->editablePageKeys as $key) {
            StaticPage::query()->firstOrCreate(['key' => $key], ['is_active' => true]);
        }
    }
}
