<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Support\AuditLogger;
use App\Support\ReservedPathSegments;
use App\Support\SiteCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $categories = Category::query()
            ->with('translations')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('key', 'like', '%'.$q.'%')
                        ->orWhereHas('translations', function ($tr) use ($q) {
                            $tr->where('name', 'like', '%'.$q.'%')
                                ->orWhere('slug', 'like', '%'.$q.'%');
                        });
                });
            })
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', compact('categories', 'q'));
    }

    public function create(): View
    {
        $locales = config('novaranews.locales');

        return view('admin.categories.create', compact('locales'));
    }

    public function store(Request $request): RedirectResponse
    {
        $locales = config('novaranews.locales');
        $rules = [
            'key' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_]+$/', 'unique:categories,key'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
        foreach ($locales as $loc) {
            $rules["translations.$loc.name"] = ['required', 'string', 'max:255'];
            $rules["translations.$loc.slug"] = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedPathSegments::all()),
                Rule::notIn(ArticleTranslation::query()->where('locale', $loc)->pluck('slug')->all()),
                Rule::unique('category_translations', 'slug')->where(fn ($q) => $q->where('locale', $loc)),
            ];
            $rules["translations.$loc.intro"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$loc.meta_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$loc.meta_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$loc.og_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$loc.og_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$loc.canonical_url"] = ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'];
            $rules["translations.$loc.og_image_url"] = ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'];
            $rules["translations.$loc.robots_noindex"] = ['nullable', 'boolean'];
            $rules["translations.$loc.robots_nofollow"] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $locales) {
            $category = Category::query()->create([
                'key' => $validated['key'],
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            foreach ($locales as $loc) {
                $t = $validated['translations'][$loc];
                CategoryTranslation::query()->create([
                    'category_id' => $category->id,
                    'locale' => $loc,
                    'name' => $t['name'],
                    'slug' => $t['slug'],
                    'intro' => $t['intro'] ?? null,
                    'meta_title' => $t['meta_title'] ?? null,
                    'meta_description' => $t['meta_description'] ?? null,
                    'og_title' => $t['og_title'] ?? null,
                    'og_description' => $t['og_description'] ?? null,
                    'canonical_url' => $t['canonical_url'] ?? null,
                    'og_image_url' => $t['og_image_url'] ?? null,
                    'robots_noindex' => (bool) ($t['robots_noindex'] ?? false),
                    'robots_nofollow' => (bool) ($t['robots_nofollow'] ?? false),
                ]);
            }
            AuditLogger::log('category.created', $category, ['key' => $category->key]);
        });

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.categories.index')->with('success', __('Category created.'));
    }

    public function edit(Category $category): View
    {
        $category->load('translations');
        $locales = config('novaranews.locales');
        $translations = $category->translations->keyBy('locale');

        return view('admin.categories.edit', compact('category', 'locales', 'translations'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $locales = config('novaranews.locales');
        $translationRowIds = [];
        foreach ($locales as $loc) {
            $translationRowIds[$loc] = CategoryTranslation::query()
                ->where('category_id', $category->id)
                ->where('locale', $loc)
                ->value('id');
        }

        $rules = [
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
        foreach ($locales as $loc) {
            $rules["translations.$loc.name"] = ['required', 'string', 'max:255'];
            $rules["translations.$loc.slug"] = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedPathSegments::all()),
                Rule::notIn(ArticleTranslation::query()->where('locale', $loc)->pluck('slug')->all()),
                Rule::unique('category_translations', 'slug')
                    ->where(fn ($q) => $q->where('locale', $loc))
                    ->ignore($translationRowIds[$loc]),
            ];
            $rules["translations.$loc.intro"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$loc.meta_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$loc.meta_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$loc.og_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.$loc.og_description"] = ['nullable', 'string', 'max:500'];
            $rules["translations.$loc.canonical_url"] = ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'];
            $rules["translations.$loc.og_image_url"] = ['nullable', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'];
            $rules["translations.$loc.robots_noindex"] = ['nullable', 'boolean'];
            $rules["translations.$loc.robots_nofollow"] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $category, $locales) {
            $category->update([
                'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            ]);

            foreach ($locales as $loc) {
                $t = $validated['translations'][$loc];
                CategoryTranslation::query()->updateOrCreate(
                    ['category_id' => $category->id, 'locale' => $loc],
                    [
                        'name' => $t['name'],
                        'slug' => $t['slug'],
                        'intro' => $t['intro'] ?? null,
                        'meta_title' => $t['meta_title'] ?? null,
                        'meta_description' => $t['meta_description'] ?? null,
                        'og_title' => $t['og_title'] ?? null,
                        'og_description' => $t['og_description'] ?? null,
                        'canonical_url' => $t['canonical_url'] ?? null,
                        'og_image_url' => $t['og_image_url'] ?? null,
                        'robots_noindex' => (bool) ($t['robots_noindex'] ?? false),
                        'robots_nofollow' => (bool) ($t['robots_nofollow'] ?? false),
                    ]
                );
            }
            AuditLogger::log('category.updated', $category, ['key' => $category->key]);
        });

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.categories.index')->with('success', __('Category updated.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->articles()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', __('Cannot delete a category that still has articles.'));
        }

        $category->translations()->delete();
        AuditLogger::log('category.deleted', $category, ['key' => $category->key]);
        $category->delete();

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.categories.index')->with('success', __('Category deleted.'));
    }
}
