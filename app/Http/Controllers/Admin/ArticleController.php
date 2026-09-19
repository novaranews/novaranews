<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\SeoRedirect;
use App\Models\User;
use App\Services\GoogleIndexingApiService;
use App\Services\ImageOptimizerService;
use App\Services\ImageSearchService;
use App\Services\EditorialToneGuardrailService;
use App\Support\ArticleContentRules;
use App\Support\ArticleSources;
use App\Support\AuditLogger;
use App\Support\ImageDimensions;
use App\Support\PublicUrl;
use App\Support\ReservedPathSegments;
use App\Support\SiteCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $locale = (string) $request->query('locale', '');
        $contentType = (string) $request->query('content_type', '');
        $publishedFrom = (string) $request->query('published_from', '');
        $publishedTo = (string) $request->query('published_to', '');

        $stats = [
            'total' => Article::query()->count(),
            'live' => Article::query()->published()->count(),
            'scheduled' => Article::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '>', now())
                ->count(),
            'draft' => Article::query()->where('status', 'draft')->count(),
            'pipeline' => Article::query()->whereIn('status', ['editor_reviewed', 'ai_ready'])->count(),
        ];

        $articles = Article::query()
            ->with(['translations', 'category.translations'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($publishedFrom !== '', fn ($q) => $q->whereDate('published_at', '>=', $publishedFrom))
            ->when($publishedTo !== '', fn ($q) => $q->whereDate('published_at', '<=', $publishedTo))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->query('category_id')))
            ->when($locale !== '', fn ($q) => $q->where('locale', $locale))
            ->when($contentType !== '', fn ($q) => $q->where('content_type', $contentType))
            ->when($search !== '', function ($q) use ($search, $locale) {
                $q->whereHas('translations', function ($trQ) use ($search, $locale) {
                    if ($locale !== '') {
                        $trQ->where('locale', $locale);
                    }
                    $trQ->where(function ($likeQ) use ($search) {
                        $likeQ->where('title', 'like', '%'.$search.'%')
                            ->orWhere('slug', 'like', '%'.$search.'%');
                    });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = Category::query()->with('translations')->orderBy('sort_order')->get();

        $indexing = app(GoogleIndexingApiService::class);
        $googleIndexingAdmin = [
            'feature_on' => $indexing->isFeatureEnabled(),
            'configured' => $indexing->isConfigured(),
            'remaining' => $indexing->dailyRemaining(),
            'limit' => $indexing->dailyLimit(),
        ];

        return view('admin.articles.index', compact(
            'articles',
            'categories',
            'search',
            'status',
            'locale',
            'contentType',
            'publishedFrom',
            'publishedTo',
            'stats',
            'googleIndexingAdmin',
        ));
    }

    /**
     * Stream all articles as JSON (same general shape as bulk import: category_key, locale, translation, …).
     */
    public function exportJson(): StreamedResponse
    {
        $filename = 'novaranews-articles-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () {
            echo '[';
            $first = true;
            Article::query()
                ->with(['translations', 'category'])
                ->orderBy('id')
                ->chunk(100, function ($articles) use (&$first) {
                    foreach ($articles as $article) {
                        if (! $first) {
                            echo ',';
                        }
                        $first = false;
                        echo json_encode($this->articleToExportArray($article), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                });
            echo ']';
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function articleToExportArray(Article $article): array
    {
        $loc = (string) $article->locale;
        $tr = $article->translations->firstWhere('locale', $loc)
            ?? $article->translations->first();

        $translation = [];
        if ($tr !== null) {
            $translation = [
                'title' => $tr->title,
                'slug' => $tr->slug,
                'excerpt' => $tr->excerpt,
                'body' => $tr->body,
                'meta_title' => $tr->meta_title,
                'meta_description' => $tr->meta_description,
                'og_title' => $tr->og_title,
                'og_description' => $tr->og_description,
                'canonical_url' => $tr->canonical_url,
                'og_image_url' => $tr->og_image_url,
                'robots_noindex' => (bool) $tr->robots_noindex,
                'robots_nofollow' => (bool) $tr->robots_nofollow,
            ];
            if (is_array($tr->sources) && $tr->sources !== []) {
                $translation['sources'] = $tr->sources;
            }
        }

        $row = [
            'id' => $article->id,
            'category_id' => $article->category_id,
            'category_key' => $article->category?->key,
            'locale' => $loc,
            'status' => $article->status,
            'published_at' => $article->published_at?->toIso8601String(),
            'user_id' => $article->user_id,
            'is_breaking' => (bool) $article->is_breaking,
            'is_editors_pick' => (bool) $article->is_editors_pick,
            'content_type' => $article->contentTypeKey(),
            'is_ai_generated' => (bool) $article->is_ai_generated,
            'editor_reviewed_at' => $article->editor_reviewed_at?->toIso8601String(),
            'hreflang_group' => $article->hreflang_group,
            'featured_image_alt' => $article->featured_image_alt,
            'featured_image_caption' => $article->featured_image_caption,
            'translation' => $translation,
        ];

        if ($article->featured_image) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
            $publicDisk = Storage::disk('public');
            $row['featured_image_url'] = url($publicDisk->url($article->featured_image));
        }

        return $row;
    }

    /**
     * Optional article fields aligned with export JSON (same keys as articleToExportArray).
     *
     * @return array{user_id: int|null, content_type: string, is_ai_generated: bool, editor_reviewed_at: mixed, hreflang_group: string|null}
     */
    private function importArticleMetaFromRow(array $row): array
    {
        $userId = null;
        if (isset($row['user_id']) && is_numeric($row['user_id'])) {
            $uid = (int) $row['user_id'];
            if ($uid > 0) {
                $userId = $uid;
            }
        }

        $contentType = is_string($row['content_type'] ?? null) ? $row['content_type'] : 'news';
        if (! in_array($contentType, Article::CONTENT_TYPES, true)) {
            $contentType = 'news';
        }

        return [
            'user_id' => $userId,
            'content_type' => $contentType,
            'is_ai_generated' => filter_var($row['is_ai_generated'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'editor_reviewed_at' => isset($row['editor_reviewed_at']) && $row['editor_reviewed_at'] !== ''
                ? $row['editor_reviewed_at']
                : null,
            'hreflang_group' => is_string($row['hreflang_group'] ?? null)
                ? (Str::slug((string) $row['hreflang_group']) ?: null)
                : null,
        ];
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:articles,id'],
            'status' => ['required', 'in:draft,ai_ready,editor_reviewed,published'],
        ]);

        $status = $validated['status'];
        $ids = $validated['ids'];

        if ($status === 'draft') {
            Article::query()
                ->whereIn('id', $ids)
                ->update(['status' => 'draft', 'published_at' => null]);
        } elseif ($status === 'published') {
            Article::query()
                ->whereIn('id', $ids)
                ->update(['status' => 'published', 'published_at' => now()]);
        } else {
            Article::query()
                ->whereIn('id', $ids)
                ->update(['status' => $status, 'published_at' => null]);
        }

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.articles.index')->with('success', __('site.admin_bulk_status_updated'));
    }

    public function create(): View
    {
        $categories = Category::query()->with('translations')->orderBy('sort_order')->get();
        $locales = config('novaranews.locales');
        $authors = User::query()->with('profileTranslations')->orderBy('name')->get();

        return view('admin.articles.create', compact('categories', 'locales', 'authors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedLocales = config('novaranews.locales');
        $base = $request->validate([
            'locale' => ['required', Rule::in($allowedLocales)],
            'category_id' => ['required', 'exists:categories,id'],
            'status' => ['required', 'in:draft,ai_ready,editor_reviewed,published'],
            'published_at' => ['nullable', 'date'],
            'content_type' => ['required', Rule::in(Article::CONTENT_TYPES)],
            'featured_image' => [Rule::requiredIf(fn () => $request->input('status') === 'published'), 'nullable', 'image', 'max:5120'],
            'featured_image_alt' => [Rule::requiredIf(fn () => $request->input('status') === 'published'), 'nullable', 'string', 'max:255'],
            'featured_image_caption' => ['nullable', 'string', 'max:500'],
            'user_id' => [
                Rule::requiredIf(fn () => $request->input('status') === 'published'),
                'nullable',
                'exists:users,id',
            ],
        ]);
        $loc = $base['locale'];
        $isBreaking = $request->boolean('is_breaking');
        $isEditorsPick = $request->boolean('is_editors_pick');

        $translation = $request->validate([
            'translation.title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('article_translations', 'title')->where(fn ($q) => $q->where('locale', $loc)),
            ],
            'translation.slug' => [
                'required',
                'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedPathSegments::all()),
                Rule::notIn(CategoryTranslation::query()->where('locale', $loc)->pluck('slug')->all()),
                Rule::unique('article_translations', 'slug')->where(fn ($q) => $q->where('locale', $loc)),
            ],
            'translation.excerpt' => ['nullable', 'string', 'max:2000'],
            'translation.body' => ['required', 'string'],
            'translation.meta_title' => ['nullable', 'string', 'max:255'],
            'translation.meta_description' => ['nullable', 'string', 'max:500'],
            'translation.og_title' => ['nullable', 'string', 'max:255'],
            'translation.og_description' => ['nullable', 'string', 'max:500'],
            'translation.canonical_url' => ['nullable', 'url', 'max:500'],
            'translation.og_image_url' => ['nullable', 'url', 'max:500'],
            'translation.robots_noindex' => ['nullable', 'boolean'],
            'translation.robots_nofollow' => ['nullable', 'boolean'],
            'translation.sources_json' => ['nullable', 'string', 'max:20000', $this->sourcesJsonRule($loc)],
        ]);

        $validated = array_merge($base, $translation);

        $bodyClean = Purify::clean($validated['translation']['body']);
        if ($validated['status'] === 'published' && ArticleContentRules::wordCount($bodyClean) < 400) {
            return back()->withErrors(['translation.body' => __('site.article_min_words', ['min' => 400])])->withInput();
        }
        if ($error = $this->validateEditorialToneForPublishing($validated, app(EditorialToneGuardrailService::class))) {
            return back()->withErrors($error)->withInput();
        }

        $path = null;
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store(
                config('novaranews.public_upload_subdir', 'media'),
                'public'
            );
            $optimizer = app(ImageOptimizerService::class);
            $optimized = $optimizer->optimizeStored($path);
            if (is_string($optimized)) {
                $path = $optimized;
            }
            $optimizer->generateThumbnail($path);
        }

        if ($validated['status'] === 'published') {
            if (! $path) {
                return back()->withErrors(['featured_image' => 'A featured image is required for published news articles.'])->withInput();
            }
            if ($error = $this->validateNewsImageQuality($path)) {
                return back()->withErrors(['featured_image' => $error])->withInput();
            }
        }

        $this->persistNewArticle($request, $validated, $path, $isBreaking, $isEditorsPick);

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.articles.index')->with('success', __('site.admin_article_saved'));
    }

    public function importJsonPaste(Request $request): RedirectResponse
    {
        $request->validate([
            'json_text' => ['required', 'string', 'max:200000'],
        ]);

        $raw = trim((string) $request->input('json_text', ''));

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return back()->withErrors(['json_text' => __('site.import_json_invalid', ['message' => $e->getMessage()])])->withInput();
        }

        // Accept single object or array (take first item)
        if (is_array($decoded) && array_values($decoded) === $decoded) {
            if (count($decoded) === 0) {
                return back()->withErrors(['json_text' => __('site.import_json_empty')])->withInput();
            }
            $row = $decoded[0];
        } elseif (is_array($decoded)) {
            $row = $decoded;
        } else {
            return back()->withErrors(['json_text' => __('site.import_json_must_be_array')])->withInput();
        }

        if (! is_array($row)) {
            return back()->withErrors(['json_text' => __('site.import_row_not_object', ['n' => 1])])->withInput();
        }

        $allowedLocales = config('novaranews.locales');

        $categoryId = $this->resolveImportCategoryId($row);
        if ($categoryId === null) {
            return back()->withErrors(['json_text' => __('site.import_row_category', ['n' => 1])])->withInput();
        }

        $loc = $row['locale'] ?? null;
        if (! is_string($loc) || ! in_array($loc, $allowedLocales, true)) {
            return back()->withErrors(['json_text' => __('site.import_row_locale', ['n' => 1])])->withInput();
        }

        $tr = $row['translation'] ?? null;
        if (! is_array($tr)) {
            return back()->withErrors(['json_text' => __('site.import_row_translation', ['n' => 1])])->withInput();
        }

        $sourcesJson = null;
        if (array_key_exists('sources_json', $tr)) {
            if (is_string($tr['sources_json'])) {
                $sourcesJson = $tr['sources_json'];
            } elseif (is_array($tr['sources_json'])) {
                $sourcesJson = json_encode($tr['sources_json']);
            }
        } elseif (isset($tr['sources']) && is_array($tr['sources'])) {
            $sourcesJson = json_encode($tr['sources']);
        }

        $payload = array_merge($this->importArticleMetaFromRow($row), [
            'locale' => $loc,
            'category_id' => $categoryId,
            'status' => $row['status'] ?? 'draft',
            'published_at' => $row['published_at'] ?? null,
            'featured_image_alt' => $row['featured_image_alt'] ?? null,
            'featured_image_caption' => $row['featured_image_caption'] ?? null,
            'translation' => [
                'title' => $tr['title'] ?? '',
                'slug' => $tr['slug'] ?? '',
                'excerpt' => $tr['excerpt'] ?? null,
                'body' => $tr['body'] ?? '',
                'meta_title' => $tr['meta_title'] ?? null,
                'meta_description' => $tr['meta_description'] ?? null,
                'og_title' => $tr['og_title'] ?? null,
                'og_description' => $tr['og_description'] ?? null,
                'canonical_url' => $tr['canonical_url'] ?? null,
                'og_image_url' => $tr['og_image_url'] ?? null,
                'robots_noindex' => filter_var($tr['robots_noindex'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'robots_nofollow' => filter_var($tr['robots_nofollow'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'sources_json' => $sourcesJson,
            ],
        ]);

        $validator = Validator::make($payload, [
            'locale' => ['required', Rule::in($allowedLocales)],
            'category_id' => ['required', 'exists:categories,id'],
            'status' => ['required', 'in:draft,ai_ready,editor_reviewed,published'],
            'published_at' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'content_type' => ['required', Rule::in(Article::CONTENT_TYPES)],
            'is_ai_generated' => ['boolean'],
            'editor_reviewed_at' => ['nullable', 'date'],
            'hreflang_group' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
            'featured_image_alt' => [Rule::requiredIf(fn () => ($payload['status'] ?? '') === 'published'), 'nullable', 'string', 'max:255'],
            'featured_image_caption' => ['nullable', 'string', 'max:500'],
            'translation.title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('article_translations', 'title')->where(fn ($q) => $q->where('locale', $loc)),
            ],
            'translation.slug' => [
                'required',
                'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedPathSegments::all()),
                Rule::notIn(CategoryTranslation::query()->where('locale', $loc)->pluck('slug')->all()),
                Rule::unique('article_translations', 'slug')->where(fn ($q) => $q->where('locale', $loc)),
            ],
            'translation.excerpt' => ['nullable', 'string', 'max:2000'],
            'translation.body' => ['required', 'string'],
            'translation.meta_title' => ['nullable', 'string', 'max:255'],
            'translation.meta_description' => ['nullable', 'string', 'max:500'],
            'translation.og_title' => ['nullable', 'string', 'max:255'],
            'translation.og_description' => ['nullable', 'string', 'max:500'],
            'translation.canonical_url' => ['nullable', 'url', 'max:500'],
            'translation.og_image_url' => ['nullable', 'url', 'max:500'],
            'translation.robots_noindex' => ['nullable', 'boolean'],
            'translation.robots_nofollow' => ['nullable', 'boolean'],
            'translation.sources_json' => ['nullable', 'string', 'max:20000', $this->sourcesJsonRule($loc)],
        ]);

        if ($validator->fails()) {
            return back()->withErrors(['json_text' => $validator->errors()->first()])->withInput();
        }

        $validated = $validator->validated();
        $bodyClean = Purify::clean($validated['translation']['body']);
        if ($validated['status'] === 'published' && ArticleContentRules::wordCount($bodyClean) < 400) {
            return back()->withErrors(['json_text' => __('site.article_min_words', ['min' => 400])])->withInput();
        }

        $isBreaking = filter_var($row['is_breaking'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isEditorsPick = filter_var($row['is_editors_pick'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $imagePath = null;
        if (! empty($row['featured_image_url']) && is_string($row['featured_image_url'])) {
            $imagePath = $this->downloadImportImage($row['featured_image_url']);
        } elseif (! empty($row['image_keywords']) && is_string($row['image_keywords'])) {
            $imgResult = app(ImageSearchService::class)->search($row['image_keywords'], $validated['locale'] ?? 'en');
            if (! empty($imgResult['path'])) {
                $imagePath = $imgResult['path'];
                if (empty($validated['featured_image_alt']) && ! empty($imgResult['alt'])) {
                    $validated['featured_image_alt'] = $imgResult['alt'];
                }
            }
        }

        if ($validated['status'] === 'published') {
            if (! $imagePath) {
                return back()->withErrors(['json_text' => 'A featured image is required for published news articles.'])->withInput();
            }
            if ($error = $this->validateNewsImageQuality($imagePath)) {
                return back()->withErrors(['json_text' => $error])->withInput();
            }
        }

        $this->persistNewArticle($request, $validated, $imagePath, $isBreaking, $isEditorsPick);
        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.articles.index')->with('success', __('site.import_articles_done', ['n' => 1]));
    }

    public function importJson(Request $request): RedirectResponse
    {
        $maxFileKb = 2048;
        $maxArticles = 100;

        $request->validate([
            'import_file' => ['required', 'file', 'max:'.$maxFileKb],
        ]);

        $file = $request->file('import_file');
        $mime = (string) $file->getMimeType();
        $name = strtolower((string) $file->getClientOriginalName());
        if (! str_ends_with($name, '.json') && $mime !== 'application/json' && $mime !== 'text/plain') {
            return back()->withErrors(['import_file' => __('site.import_file_must_be_json')]);
        }

        $raw = file_get_contents($file->getRealPath() ?: $file->getPathname());
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return back()->withErrors(['import_file' => __('site.import_json_invalid', ['message' => $e->getMessage()])]);
        }

        if (! is_array($decoded) || array_values($decoded) !== $decoded) {
            return back()->withErrors(['import_file' => __('site.import_json_must_be_array')]);
        }

        if (count($decoded) === 0) {
            return back()->withErrors(['import_file' => __('site.import_json_empty')]);
        }

        if (count($decoded) > $maxArticles) {
            return back()->withErrors(['import_file' => __('site.import_json_max', ['max' => $maxArticles])]);
        }

        $allowedLocales = config('novaranews.locales');
        $rowErrors = [];
        $success = 0;

        foreach ($decoded as $index => $row) {
            $rowNum = $index + 1;
            if (! is_array($row)) {
                $rowErrors[] = __('site.import_row_not_object', ['n' => $rowNum]);

                continue;
            }

            $categoryId = $this->resolveImportCategoryId($row);
            if ($categoryId === null) {
                $rowErrors[] = __('site.import_row_category', ['n' => $rowNum]);

                continue;
            }

            $loc = $row['locale'] ?? null;
            if (! is_string($loc) || ! in_array($loc, $allowedLocales, true)) {
                $rowErrors[] = __('site.import_row_locale', ['n' => $rowNum]);

                continue;
            }

            $tr = $row['translation'] ?? null;
            if (! is_array($tr)) {
                $rowErrors[] = __('site.import_row_translation', ['n' => $rowNum]);

                continue;
            }

            $sourcesJson = null;
            if (array_key_exists('sources_json', $tr)) {
                if (is_string($tr['sources_json'])) {
                    $sourcesJson = $tr['sources_json'];
                } elseif (is_array($tr['sources_json'])) {
                    $sourcesJson = json_encode($tr['sources_json']);
                }
            } elseif (isset($tr['sources']) && is_array($tr['sources'])) {
                $sourcesJson = json_encode($tr['sources']);
            }

            $payload = array_merge($this->importArticleMetaFromRow($row), [
                'locale' => $loc,
                'category_id' => $categoryId,
                'status' => $row['status'] ?? 'draft',
                'published_at' => $row['published_at'] ?? null,
                'featured_image_alt' => $row['featured_image_alt'] ?? null,
                'featured_image_caption' => $row['featured_image_caption'] ?? null,
                'translation' => [
                    'title' => $tr['title'] ?? '',
                    'slug' => $tr['slug'] ?? '',
                    'excerpt' => $tr['excerpt'] ?? null,
                    'body' => $tr['body'] ?? '',
                    'meta_title' => $tr['meta_title'] ?? null,
                    'meta_description' => $tr['meta_description'] ?? null,
                    'og_title' => $tr['og_title'] ?? null,
                    'og_description' => $tr['og_description'] ?? null,
                    'canonical_url' => $tr['canonical_url'] ?? null,
                    'og_image_url' => $tr['og_image_url'] ?? null,
                    'robots_noindex' => filter_var($tr['robots_noindex'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'robots_nofollow' => filter_var($tr['robots_nofollow'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sources_json' => $sourcesJson,
                ],
            ]);

            $validator = Validator::make($payload, [
                'locale' => ['required', Rule::in($allowedLocales)],
                'category_id' => ['required', 'exists:categories,id'],
                'status' => ['required', 'in:draft,ai_ready,editor_reviewed,published'],
                'published_at' => ['nullable', 'date'],
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'content_type' => ['required', Rule::in(Article::CONTENT_TYPES)],
                'is_ai_generated' => ['boolean'],
                'editor_reviewed_at' => ['nullable', 'date'],
                'hreflang_group' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
                'featured_image_alt' => [Rule::requiredIf(fn () => ($payload['status'] ?? '') === 'published'), 'nullable', 'string', 'max:255'],
                'featured_image_caption' => ['nullable', 'string', 'max:500'],
                'translation.title' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('article_translations', 'title')->where(fn ($q) => $q->where('locale', $loc)),
                ],
                'translation.slug' => [
                    'required',
                    'string', 'max:255',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::notIn(ReservedPathSegments::all()),
                    Rule::notIn(CategoryTranslation::query()->where('locale', $loc)->pluck('slug')->all()),
                    Rule::unique('article_translations', 'slug')->where(fn ($q) => $q->where('locale', $loc)),
                ],
                'translation.excerpt' => ['nullable', 'string', 'max:2000'],
                'translation.body' => ['required', 'string'],
                'translation.meta_title' => ['nullable', 'string', 'max:255'],
                'translation.meta_description' => ['nullable', 'string', 'max:500'],
                'translation.og_title' => ['nullable', 'string', 'max:255'],
                'translation.og_description' => ['nullable', 'string', 'max:500'],
                'translation.canonical_url' => ['nullable', 'url', 'max:500'],
                'translation.og_image_url' => ['nullable', 'url', 'max:500'],
                'translation.robots_noindex' => ['nullable', 'boolean'],
                'translation.robots_nofollow' => ['nullable', 'boolean'],
                'translation.sources_json' => ['nullable', 'string', 'max:20000', $this->sourcesJsonRule($loc)],
            ]);

            if ($validator->fails()) {
                $rowErrors[] = __('site.import_row_validation', [
                    'n' => $rowNum,
                    'msg' => $validator->errors()->first(),
                ]);

                continue;
            }

            $validated = $validator->validated();
            $bodyClean = Purify::clean($validated['translation']['body']);
            if ($validated['status'] === 'published' && ArticleContentRules::wordCount($bodyClean) < 400) {
                $rowErrors[] = __('site.import_row_validation', [
                    'n' => $rowNum,
                    'msg' => __('site.article_min_words', ['min' => 400]),
                ]);

                continue;
            }

            $isBreaking = filter_var($row['is_breaking'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $isEditorsPick = filter_var($row['is_editors_pick'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $imagePath = null;
            if (! empty($row['featured_image_url']) && is_string($row['featured_image_url'])) {
                $imagePath = $this->downloadImportImage($row['featured_image_url']);
            }

            if ($validated['status'] === 'published') {
                if (! $imagePath) {
                    $rowErrors[] = __('site.import_row_validation', [
                        'n' => $rowNum,
                        'msg' => 'A featured image is required for published news articles.',
                    ]);
                    continue;
                }
                if ($error = $this->validateNewsImageQuality($imagePath)) {
                    $rowErrors[] = __('site.import_row_validation', [
                        'n' => $rowNum,
                        'msg' => $error,
                    ]);
                    continue;
                }
            }

            $this->persistNewArticle($request, $validated, $imagePath, $isBreaking, $isEditorsPick);
            $success++;
        }

        if ($success > 0) {
            SiteCache::forgetHomeAndNav();
        }

        if ($success === 0) {
            return back()->withErrors([
                'import_file' => implode("\n", $rowErrors),
            ]);
        }

        $message = __('site.import_articles_done', ['n' => $success]);
        if ($rowErrors !== []) {
            $message .= ' '.__('site.import_articles_skipped', ['errors' => implode(' ', $rowErrors)]);
        }

        return redirect()->route('admin.articles.index')->with('success', $message);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isUrlSafeToFetch(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        // Block internal/loopback/link-local/private ranges
        if (preg_match('/^(localhost|127\.|0\.|10\.|192\.168\.|169\.254\.|::1$|fc00:|fe80:)/i', $host)) {
            return false;
        }
        // Block private class B ranges (172.16-31.x.x)
        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./i', $host)) {
            return false;
        }
        return true;
    }

    private function downloadImportImage(string $url): ?string
    {
        if (! $this->isUrlSafeToFetch($url)) {
            return null;
        }
        try {
            $response = Http::timeout(15)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $ext = 'jpg';
            $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
            $pathExt = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
            if (in_array($pathExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = $pathExt === 'jpeg' ? 'jpg' : $pathExt;
            }

            $subdir   = config('novaranews.public_upload_subdir', 'media');
            $filename = $subdir.'/'.Str::uuid().'.'.$ext;
            $optimizer = app(ImageOptimizerService::class);
            $binary   = $optimizer->optimizeRaw($response->body());
            Storage::disk('public')->put($filename, $binary);
            $optimized = $optimizer->optimizeStored($filename);
            $finalPath = is_string($optimized) ? $optimized : $filename;
            $optimizer->generateThumbnail($finalPath);

            return $finalPath;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveImportCategoryId(array $row): ?int
    {
        if (isset($row['category_id'])) {
            $id = (int) $row['category_id'];
            if ($id < 1 || ! Category::query()->whereKey($id)->exists()) {
                return null;
            }

            return $id;
        }

        if (isset($row['category_key']) && is_string($row['category_key'])) {
            $cat = Category::query()->where('key', $row['category_key'])->first();

            return $cat?->id;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistNewArticle(Request $request, array $validated, ?string $featuredImagePath, bool $isBreaking, bool $isEditorsPick = false): void
    {
        $loc = $validated['locale'];
        DB::transaction(function () use ($validated, $request, $featuredImagePath, $loc, $isBreaking, $isEditorsPick) {
            $authorId = $validated['user_id'] ?? $request->user()->id;

            $article = Article::query()->create([
                'category_id' => $validated['category_id'],
                'user_id' => $authorId,
                'locale' => $loc,
                'status' => $validated['status'],
                'published_at' => $validated['status'] === 'published'
                    ? ($validated['published_at'] ?? now())
                    : null,
                'featured_image' => $featuredImagePath,
                'featured_image_alt' => $validated['featured_image_alt'] ?? null,
                'featured_image_caption' => $validated['featured_image_caption'] ?? null,
                'is_breaking' => $isBreaking,
                'is_editors_pick' => $isEditorsPick,
                'content_type' => $validated['content_type'] ?? 'news',
                'is_ai_generated' => (bool) ($validated['is_ai_generated'] ?? false),
                'editor_reviewed_at' => isset($validated['editor_reviewed_at']) && $validated['editor_reviewed_at'] !== null && $validated['editor_reviewed_at'] !== ''
                    ? Carbon::parse($validated['editor_reviewed_at'])
                    : null,
                'hreflang_group' => $validated['hreflang_group'] ?? null,
            ]);

            $t = $validated['translation'];
            $canonicalUrl = $this->buildArticleCanonicalUrl($loc, (string) $t['slug'], $validated['content_type'] ?? 'news');
            $ogImageUrl = $this->buildArticleOgImageUrl($featuredImagePath);
            $translation = ArticleTranslation::query()->create([
                'article_id' => $article->id,
                'locale' => $loc,
                'title' => $t['title'],
                'slug' => $t['slug'],
                'excerpt' => $t['excerpt'] ?? null,
                'body' => Purify::clean($t['body']),
                'meta_title' => $t['meta_title'] ?? null,
                'meta_description' => $t['meta_description'] ?? null,
                'og_title' => $t['og_title'] ?? null,
                'og_description' => $t['og_description'] ?? null,
                'canonical_url' => $canonicalUrl,
                'og_image_url' => $ogImageUrl,
                'robots_noindex' => (bool) ($t['robots_noindex'] ?? false),
                'robots_nofollow' => (bool) ($t['robots_nofollow'] ?? false),
                'sources' => $this->parseSourcesJson($t['sources_json'] ?? null),
            ]);

            $this->snapshotRevision($article, $translation, Auth::id(), 'create');
            AuditLogger::log('article.created', $article, [
                'locale' => $loc,
                'status' => $article->status,
                'slug' => $translation->slug,
            ]);
        });
    }

    public function edit(Article $article): View
    {
        $article->load(['translations', 'category.translations']);
        $categories = Category::query()->with('translations')->orderBy('sort_order')->get();
        $authors = User::query()->with('profileTranslations')->orderBy('name')->get();
        $articleLocale = (string) $article->locale;
        $translation = $article->translations->firstWhere('locale', $articleLocale)
            ?? $article->translations->first();

        $signedPreviewUrls = [];
        if ($translation) {
            URL::forceScheme('https');
            $signedPreviewUrls[$articleLocale] = URL::signedRoute(
                'article.preview',
                ['locale' => $articleLocale, 'article' => $article->id],
                now()->addDays(7)
            );
        }

        $featuredDims = $article->featured_image ? ImageDimensions::forStoragePublic($article->featured_image) : null;
        $articleReadiness = [
            'has_featured_image' => (bool) $article->featured_image,
            'featured_image_width' => (int) ($featuredDims['width'] ?? 0),
        ];

        $indexing = app(GoogleIndexingApiService::class);
        $googleIndexing = [
            'feature_on' => $indexing->isFeatureEnabled(),
            'configured' => $indexing->isConfigured(),
            'remaining' => $indexing->dailyRemaining(),
            'limit' => $indexing->dailyLimit(),
            'eligible' => $article->googleIndexingEligible(),
            'needs_notify' => $article->googleIndexingNeedsNotify(),
            'notified_at' => $article->google_indexing_notified_at,
        ];

        return view('admin.articles.edit', compact(
            'article',
            'categories',
            'authors',
            'articleLocale',
            'translation',
            'signedPreviewUrls',
            'articleReadiness',
            'googleIndexing'
        ));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        if ($request->input('user_id') === '') {
            $request->merge(['user_id' => null]);
        }

        $allowedLocales = config('novaranews.locales');
        $oldLoc = (string) $article->locale;
        $translationRowId = ArticleTranslation::query()
            ->where('article_id', $article->id)
            ->where('locale', $oldLoc)
            ->value('id');

        $base = $request->validate([
            'locale' => ['required', Rule::in($allowedLocales)],
            'category_id' => ['required', 'exists:categories,id'],
            'status' => ['required', 'in:draft,ai_ready,editor_reviewed,published'],
            'published_at' => ['nullable', 'date'],
            'content_type' => ['required', Rule::in(Article::CONTENT_TYPES)],
            'featured_image' => [Rule::requiredIf(fn () => $request->input('status') === 'published' && ! $article->featured_image), 'nullable', 'image', 'max:5120'],
            'featured_image_alt' => [Rule::requiredIf(fn () => $request->input('status') === 'published'), 'nullable', 'string', 'max:255'],
            'featured_image_caption' => ['nullable', 'string', 'max:500'],
            'hreflang_group' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
            'user_id' => [
                Rule::requiredIf(fn () => $request->input('status') === 'published'),
                'nullable',
                'exists:users,id',
            ],
        ]);
        // New (possibly changed) locale; uniqueness checks below scope to this locale.
        $loc = (string) $base['locale'];
        $isBreaking = $request->boolean('is_breaking');
        $isEditorsPick = $request->boolean('is_editors_pick');

        $translation = $request->validate([
            'translation.title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('article_translations', 'title')
                    ->where(fn ($q) => $q->where('locale', $loc))
                    ->ignore($translationRowId),
            ],
            'translation.slug' => [
                'required',
                'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedPathSegments::all()),
                Rule::notIn(CategoryTranslation::query()->where('locale', $loc)->pluck('slug')->all()),
                Rule::unique('article_translations', 'slug')
                    ->where(fn ($q) => $q->where('locale', $loc))
                    ->ignore($translationRowId),
            ],
            'translation.excerpt' => ['nullable', 'string', 'max:2000'],
            'translation.body' => ['required', 'string'],
            'translation.meta_title' => ['nullable', 'string', 'max:255'],
            'translation.meta_description' => ['nullable', 'string', 'max:500'],
            'translation.og_title' => ['nullable', 'string', 'max:255'],
            'translation.og_description' => ['nullable', 'string', 'max:500'],
            'translation.canonical_url' => ['nullable', 'url', 'max:500'],
            'translation.og_image_url' => ['nullable', 'url', 'max:500'],
            'translation.robots_noindex' => ['nullable', 'boolean'],
            'translation.robots_nofollow' => ['nullable', 'boolean'],
            'translation.sources_json' => ['nullable', 'string', 'max:20000', $this->sourcesJsonRule($loc)],
        ]);

        $validated = array_merge($base, $translation);

        $bodyClean = Purify::clean($validated['translation']['body']);
        if ($validated['status'] === 'published' && ArticleContentRules::wordCount($bodyClean) < 400) {
            return back()->withErrors(['translation.body' => __('site.article_min_words', ['min' => 400])])->withInput();
        }

        if ($validated['status'] === 'published' && empty($validated['user_id']) && ! $article->user_id) {
            return back()->withErrors(['user_id' => __('site.article_author_required')])->withInput();
        }
        if ($error = $this->validateEditorialToneForPublishing($validated, app(EditorialToneGuardrailService::class))) {
            return back()->withErrors($error)->withInput();
        }

        if ($request->hasFile('featured_image')) {
            if ($article->featured_image) {
                $this->deleteImageWithThumb($article->featured_image);
            }
            $article->featured_image = $request->file('featured_image')->store(
                config('novaranews.public_upload_subdir', 'media'),
                'public'
            );
            $optimizer = app(ImageOptimizerService::class);
            $optimized = $optimizer->optimizeStored($article->featured_image);
            if (is_string($optimized)) {
                $article->featured_image = $optimized;
            }
            $optimizer->generateThumbnail($article->featured_image);
        }

        $featuredPath = $article->featured_image;

        if ($validated['status'] === 'published') {
            if (! $featuredPath) {
                return back()->withErrors(['featured_image' => 'A featured image is required for published news articles.'])->withInput();
            }
            if ($error = $this->validateNewsImageQuality($featuredPath)) {
                return back()->withErrors(['featured_image' => $error])->withInput();
            }
        }

        DB::transaction(function () use ($validated, $article, $loc, $oldLoc, $featuredPath, $isBreaking, $isEditorsPick, $request) {
            $oldTranslation = ArticleTranslation::query()
                ->where('article_id', $article->id)
                ->where('locale', $oldLoc)
                ->first();
            if ($oldTranslation) {
                $this->snapshotRevision($article, $oldTranslation, Auth::id(), 'before_update');
            }

            // Capture old content_type before update so the old URL path segment is correct
            $oldContentType = $article->contentTypeKey();

            $publishedAt = $article->published_at;
            if (in_array($validated['status'], ['draft', 'ai_ready', 'editor_reviewed'], true)) {
                $publishedAt = null;
            } elseif ($validated['status'] === 'published') {
                if (! empty($validated['published_at'])) {
                    $publishedAt = $validated['published_at'];
                } elseif (! $publishedAt) {
                    $publishedAt = now();
                }
            }

            $authorId = $validated['user_id'] ?? $article->user_id;

            $article->update([
                'locale' => $loc,
                'category_id' => $validated['category_id'],
                'user_id' => $authorId,
                'status' => $validated['status'],
                'published_at' => $publishedAt,
                'is_breaking' => $isBreaking,
                'is_editors_pick' => $isEditorsPick,
                'content_type' => $validated['content_type'],
                'featured_image' => $featuredPath,
                'featured_image_alt' => $validated['featured_image_alt'] ?? null,
                'featured_image_caption' => $validated['featured_image_caption'] ?? null,
                'hreflang_group' => $validated['hreflang_group'] ?? null,
            ]);

            $t = $validated['translation'];
            $canonicalUrl = $this->buildArticleCanonicalUrl($loc, (string) $t['slug'], $validated['content_type'] ?? 'news');
            $ogImageUrl = $this->buildArticleOgImageUrl($featuredPath);
            // Match by the OLD locale so a locale change renames the existing row instead of orphaning it.
            $translation = ArticleTranslation::query()->updateOrCreate(
                ['article_id' => $article->id, 'locale' => $oldLoc],
                [
                    'locale' => $loc,
                    'title' => $t['title'],
                    'slug' => $t['slug'],
                    'excerpt' => $t['excerpt'] ?? null,
                    'body' => Purify::clean($t['body']),
                    'meta_title' => $t['meta_title'] ?? null,
                    'meta_description' => $t['meta_description'] ?? null,
                    'og_title' => $t['og_title'] ?? null,
                    'og_description' => $t['og_description'] ?? null,
                    'canonical_url' => $canonicalUrl,
                    'og_image_url' => $ogImageUrl,
                    'robots_noindex' => (bool) ($t['robots_noindex'] ?? false),
                    'robots_nofollow' => (bool) ($t['robots_nofollow'] ?? false),
                    'sources' => $this->parseSourcesJson($t['sources_json'] ?? null),
                ]
            );

            // Redirect the old URL (slug, locale and/or content-type may have changed).
            $urlChanged = $oldTranslation && (
                $oldTranslation->slug !== $translation->slug
                || $oldLoc !== $loc
                || $oldContentType !== $article->contentTypeKey()
            );
            if ($urlChanged) {
                SeoRedirect::query()->updateOrCreate(
                    ['locale' => $oldLoc, 'from_path' => trim(article_path_segment($oldLoc, $oldContentType).'/'.$oldTranslation->slug, '/')],
                    ['to_url' => $this->buildArticleCanonicalUrl($loc, (string) $translation->slug, $article->contentTypeKey()), 'status_code' => 301, 'is_active' => true]
                );
            }

            $this->snapshotRevision($article, $translation, Auth::id(), 'update');
            AuditLogger::log('article.updated', $article, [
                'locale' => $loc,
                'status' => $article->status,
                'slug' => $translation->slug,
            ]);
        });

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.articles.edit', $article)->with('success', __('site.admin_article_updated'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        AuditLogger::log('article.deleted', $article, [
            'locale' => $article->locale,
            'status' => $article->status,
        ]);
        if ($article->featured_image) {
            $this->deleteImageWithThumb($article->featured_image);
        }
        $article->delete();

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.articles.index')->with('success', __('site.admin_article_deleted'));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:articles,id'],
        ]);

        $articles = Article::query()->whereIn('id', $validated['ids'])->get();
        foreach ($articles as $article) {
            AuditLogger::log('article.deleted', $article, [
                'locale' => $article->locale,
                'status' => $article->status,
            ]);
            if ($article->featured_image) {
                $this->deleteImageWithThumb($article->featured_image);
            }
            $article->delete();
        }

        SiteCache::forgetHomeAndNav();

        return redirect()->route('admin.articles.index')->with('success', __('Selected articles deleted.'));
    }

    private function validateNewsImageQuality(string $storagePath): ?string
    {
        $dims = ImageDimensions::forStoragePublic($storagePath);
        if (! $dims) {
            return 'Featured image dimensions could not be detected. Please upload a valid image.';
        }

        $w = (int) ($dims['width'] ?? 0);
        $h = (int) ($dims['height'] ?? 0);
        if ($w < 1200) {
            return 'Featured image width should be at least 1200px for Google News.';
        }
        if ($h > 0 && ($w / $h) < 1.2) {
            return 'Featured image aspect ratio is too narrow. Please use a wider image.';
        }

        return null;
    }

    private function buildArticleCanonicalUrl(string $locale, string $slug, string $contentType = 'news'): string
    {
        return route('article.show', [
            'locale' => $locale,
            'articlePrefix' => article_path_segment($locale, $contentType),
            'articleSlug' => $slug,
        ]);
    }

    private function buildArticleOgImageUrl(?string $featuredImagePath): ?string
    {
        if (! filled($featuredImagePath)) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $diskPath = $publicDisk->url($featuredImagePath);

        if (preg_match('/^https?:\/\//i', $diskPath) === 1) {
            return $diskPath;
        }

        return url($diskPath);
    }

    private function snapshotRevision(Article $article, ArticleTranslation $translation, ?int $userId, string $reason): void
    {
        ArticleRevision::query()->create([
            'article_id' => $article->id,
            'article_translation_id' => $translation->id,
            'user_id' => $userId,
            'locale' => $translation->locale,
            'reason' => $reason,
            'snapshot' => [
                'article' => [
                    'status' => $article->status,
                    'published_at' => optional($article->published_at)->toAtomString(),
                    'category_id' => $article->category_id,
                    'featured_image' => $article->featured_image,
                    'featured_image_alt' => $article->featured_image_alt,
                    'featured_image_caption' => $article->featured_image_caption,
                    'content_type' => $article->content_type,
                    'is_breaking' => (bool) $article->is_breaking,
                    'is_editors_pick' => (bool) $article->is_editors_pick,
                ],
                'translation' => [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'excerpt' => $translation->excerpt,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                    'og_title' => $translation->og_title,
                    'og_description' => $translation->og_description,
                    'canonical_url' => $translation->canonical_url,
                    'og_image_url' => $translation->og_image_url,
                    'robots_noindex' => (bool) $translation->robots_noindex,
                    'robots_nofollow' => (bool) $translation->robots_nofollow,
                    'body' => $translation->body,
                    'sources' => $translation->sources,
                ],
            ],
        ]);
    }

    private function parseSourcesJson(?string $sourcesJson): ?array
    {
        $text = trim((string) $sourcesJson);
        if ($text === '') {
            return null;
        }

        // Plain text: "Reuters, AP, Sabah" → ["Reuters", "AP", "Sabah"]
        if (str_starts_with($text, '[') || str_starts_with($text, '{')) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $sources = array_is_list($decoded) ? $decoded : [$decoded];
                $normalized = ArticleSources::normalize($sources);

                return $normalized !== [] ? $normalized : null;
            }
        }

        $names = array_values(array_filter(array_map('trim', explode(',', $text))));

        $normalized = ArticleSources::normalize($names);

        return $normalized !== [] ? $normalized : null;
    }

    private function sourcesJsonRule(string $locale): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($locale): void {
            $text = trim((string) $value);
            if ($text === '' || (! str_starts_with($text, '[') && ! str_starts_with($text, '{'))) {
                return;
            }

            json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $fail('Sources must be valid JSON or comma-separated source names.');
            }
        };
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, string>|null
     */
    private function validateEditorialToneForPublishing(array $validated, EditorialToneGuardrailService $guardrail): ?array
    {
        if (($validated['status'] ?? null) !== 'published') {
            return null;
        }

        $tr = $validated['translation'] ?? [];
        $title = trim((string) ($tr['meta_title'] ?? $tr['title'] ?? ''));
        $description = trim((string) ($tr['meta_description'] ?? $tr['excerpt'] ?? ''));
        $analysis = $guardrail->analyze($title, $description);

        if (($analysis['risk'] ?? 'low') === 'high' && $guardrail->shouldBlockHighRiskOnPublish()) {
            return [
                'translation.meta_title' => __('site.admin_editorial_guardrail_blocked'),
            ];
        }

        return null;
    }

    /**
     * Delete a stored image and its auto-generated thumbnail (if it exists).
     */
    private function deleteImageWithThumb(string $storagePath): void
    {
        $disk = Storage::disk('public');
        $disk->delete($storagePath);

        $dir      = dirname($storagePath);
        $base     = pathinfo($storagePath, PATHINFO_FILENAME);
        $thumbDir = ($dir === '.' || $dir === '') ? ImageOptimizerService::THUMB_SUBDIR : $dir.'/'.ImageOptimizerService::THUMB_SUBDIR;
        $thumbPath = $thumbDir.'/'.$base.'.webp';

        if ($disk->exists($thumbPath)) {
            $disk->delete($thumbPath);
        }
    }
}
