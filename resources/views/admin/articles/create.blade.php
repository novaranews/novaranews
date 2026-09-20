<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_new_article') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            <div class="admin-card p-4 sm:p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('site.import_articles_heading') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('admin.articles.import-example') }}" class="text-indigo-600 underline hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200" download="import-articles.example.json">{{ __('site.import_articles_example_link') }}</a>
                    <span class="text-gray-400 dark:text-gray-500">-</span>
                    <code class="rounded bg-gray-100 px-1 dark:bg-gray-800 dark:text-gray-200">docs/import-articles.example.json</code>
                </p>

                {{-- Tab buttons --}}
                <div class="mt-4 flex gap-1 border-b border-gray-200 dark:border-gray-700">
                    <button type="button" id="tab-file-btn"
                        class="rounded-t border border-b-0 border-gray-200 bg-white px-4 py-1.5 text-sm font-medium text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        onclick="switchImportTab('file')">
                        {{ __('site.admin_articles_file_upload_tab') }}
                    </button>
                    <button type="button" id="tab-paste-btn"
                        class="rounded-t border border-b-0 border-transparent px-4 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        onclick="switchImportTab('paste')">
                        {{ __('site.admin_articles_json_paste_tab') }}
                    </button>
                </div>

                {{-- File upload tab --}}
                <div id="tab-file" class="pt-4">
                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ __('site.import_articles_desc', ['max' => 100]) }}</p>
                    <form method="post" action="{{ route('admin.articles.import-json') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-[12rem] flex-1">
                            <x-input-label for="import_file" :value="__('site.import_articles_json_file_label')" />
                            <input id="import_file" name="import_file" type="file" accept=".json,application/json" class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300" required />
                            <x-input-error :messages="$errors->get('import_file')" class="mt-2" />
                        </div>
                        <x-secondary-button type="submit">{{ __('site.import_articles_button') }}</x-secondary-button>
                    </form>
                </div>

                {{-- Paste JSON tab --}}
                <div id="tab-paste" class="hidden pt-4">
                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('site.admin_articles_paste_desc') }}
                    </p>
                    <form method="post" action="{{ route('admin.articles.import-json-paste') }}" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="json_text" :value="__('site.admin_json_label')" />
                            <textarea id="json_text" name="json_text"
                                rows="14"
                                placeholder='{
  "locale": "tr",
  "category_key": "technology",
  "status": "published",
  "featured_image_url": "https://...",
  "translation": {
    "title": "...",
    "slug": "...",
    "body": "<p>...</p>"
  }
}'
                                class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                required>{{ old('json_text') }}</textarea>
                            <x-input-error :messages="$errors->get('json_text')" class="mt-2" />
                        </div>
                        <x-secondary-button type="submit">{{ __('site.admin_articles_create_from_json') }}</x-secondary-button>
                    </form>
                </div>
            </div>

            <form method="post" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data" class="admin-form-card" data-admin-article-form novalidate>
                @csrf
                <div class="sticky top-2 z-20 -mx-4 rounded-lg border border-gray-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-700 dark:bg-gray-900/95">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap gap-2" data-admin-article-tabs>
                            <button type="button" class="admin-tab-btn is-active" data-admin-article-tab="publishing">Yayin</button>
                            <button type="button" class="admin-tab-btn" data-admin-article-tab="image">Gorsel</button>
                            <button type="button" class="admin-tab-btn" data-admin-article-tab="content">Icerik</button>
                            <button type="button" class="admin-tab-btn" data-admin-article-tab="seo">SEO</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-primary-button>{{ __('site.common_save') }}</x-primary-button>
                            <x-admin.btn :href="route('admin.articles.index')">{{ __('site.common_cancel') }}</x-admin.btn>
                        </div>
                    </div>
                </div>

                @if ($errors->any() && ! $errors->has('import_file') && ! $errors->has('json_text'))
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200">
                        <p class="font-semibold">{{ __('site.admin_fix_highlighted_errors') }}</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-4 pt-2 sm:grid-cols-3" data-admin-article-panel="publishing">
                    <div>
                        <x-input-label for="category_id" :value="__('site.common_category')" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" required>
                            @foreach($categories as $cat)
                                @php $lab = $cat->translate('en')?->name ?? $cat->key; @endphp
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $lab }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="locale" :value="__('site.language')" />
                        <select id="locale" name="locale" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" required>
                            @foreach($locales as $l)
                                <option value="{{ $l }}" @selected(old('locale', config('novaranews.default_locale', 'en')) === $l)>{{ strtoupper($l) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('locale')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('site.admin_status_label')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="draft" @selected(old('status', 'draft') === 'draft')>{{ __('site.admin_draft') }}</option>
                            <option value="ai_ready" @selected(old('status') === 'ai_ready')>{{ __('site.admin_ai_ready') }}</option>
                            <option value="editor_reviewed" @selected(old('status') === 'editor_reviewed')>{{ __('site.admin_editor_reviewed') }}</option>
                            <option value="published" @selected(old('status') === 'published')>{{ __('site.admin_published') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>
                <div data-admin-article-panel="publishing">
                    <x-input-label for="published_at" :value="__('site.admin_published_at')" />
                    <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full" :value="old('published_at')" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_leave_empty_now_hint') }}</p>
                    <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
                </div>
                <div class="space-y-2" data-admin-article-panel="publishing">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="checkbox" name="is_breaking" value="1" class="rounded border-gray-300" @checked(old('is_breaking'))>
                        {{ __('site.breaking_checkbox_help') }}
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="checkbox" name="is_editors_pick" value="1" class="rounded border-gray-300" @checked(old('is_editors_pick'))>
                        {{ __('site.editors_pick_checkbox_help') }}
                    </label>
                </div>
                <div data-admin-article-panel="publishing">
                    <x-input-label for="content_type" :value="__('site.admin_content_type')" />
                    <select id="content_type" name="content_type" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                        @foreach(\App\Models\Article::CONTENT_TYPES as $ct)
                            <option value="{{ $ct }}" @selected(old('content_type', 'news') === $ct)>{{ __('site.content_type_'.$ct) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_content_type_help') }}</p>
                    <x-input-error :messages="$errors->get('content_type')" class="mt-2" />
                </div>
                <div data-admin-article-panel="publishing">
                    <x-input-label for="user_id" :value="__('site.common_author')" />
                    <select id="user_id" name="user_id" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                        @foreach($authors as $a)
                            <option
                                value="{{ $a->id }}"
                                data-has-slug="{{ filled($a->slug) ? '1' : '0' }}"
                                data-has-title="{{ filled($a->title) ? '1' : '0' }}"
                                data-bio-words="{{ $a->profileBioWordCountForDefaultLocale() }}"
                                @selected((int) old('user_id', auth()->id()) === $a->id)
                            >{{ $a->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_author_when_published') }}</p>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                </div>
                <div data-admin-article-panel="image">
                    <x-input-label for="featured_image_picker" :value="__('site.admin_featured_image')" />
                    <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Google News: minimum 1200×628 px · 16:9 recommended</p>
                    <input id="featured_image_picker" type="file" accept="image/*" class="mt-1 block w-full text-sm" />
                    <input id="featured_image" name="featured_image" type="file" class="hidden" />
                    <x-input-error :messages="$errors->get('featured_image')" class="mt-2" />
                    <div id="crop-preview-wrap" class="mt-2 hidden">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Crop preview:</p>
                        <img id="crop-preview-img" src="" alt="Crop preview"
                            class="mt-1 h-24 w-auto rounded border border-gray-300 dark:border-gray-600" />
                        <p id="crop-dims" class="mt-1 text-xs text-green-600 dark:text-green-400"></p>
                    </div>
                </div>

                <div id="cropper-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
                    <div class="flex w-full max-w-3xl flex-col rounded-lg bg-white shadow-2xl dark:bg-gray-900">
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Crop image — Google News (1200×628 · 16:9)</h3>
                            <button type="button" id="cropper-cancel" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                        </div>
                        <div class="max-h-[60vh] overflow-hidden bg-gray-100 dark:bg-gray-800">
                            <img id="cropper-source" src="" alt="" class="block max-w-full" />
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Drag and resize the crop area.</span>
                            <div class="flex gap-2">
                                <button type="button" id="cropper-cancel-btn"
                                    class="rounded border border-gray-300 bg-white px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                    Cancel
                                </button>
                                <button type="button" id="cropper-apply"
                                    class="rounded bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Apply crop
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div data-admin-article-panel="image">
                    <x-input-label for="featured_image_alt" :value="__('site.admin_featured_image_alt')" />
                    <x-text-input id="featured_image_alt" name="featured_image_alt" type="text" class="mt-1 block w-full" :value="old('featured_image_alt')" />
                    <x-input-error :messages="$errors->get('featured_image_alt')" class="mt-2" />
                </div>
                <div data-admin-article-panel="image">
                    <x-input-label for="featured_image_caption" :value="__('site.admin_featured_image_caption_optional')" />
                    <x-text-input id="featured_image_caption" name="featured_image_caption" type="text" class="mt-1 block w-full" :value="old('featured_image_caption')" />
                    <x-input-error :messages="$errors->get('featured_image_caption')" class="mt-2" />
                </div>

                <div class="space-y-3">
                        <div data-admin-article-panel="content">
                            <x-input-label for="title" :value="__('site.admin_title_label')" />
                            <x-text-input id="title" class="mt-1 block w-full" type="text" name="translation[title]" :value="old('translation.title')" />
                            <x-input-error :messages="$errors->get('translation.title')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="slug" :value="__('site.admin_url_slug_extended_label')" />
                            <x-text-input id="slug" class="mt-1 block w-full font-mono text-sm" type="text" name="translation[slug]" :value="old('translation.slug')" />
                            <x-input-error :messages="$errors->get('translation.slug')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="excerpt" :value="__('site.admin_excerpt_label')" />
                            <textarea id="excerpt" name="translation[excerpt]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">{{ old('translation.excerpt') }}</textarea>
                            <x-input-error :messages="$errors->get('translation.excerpt')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="body" :value="__('site.admin_body_html_allowed_label')" />
                            <textarea id="body" name="translation[body]" rows="22" class="tinymce-body mt-1 block min-h-[28rem] w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">{{ old('translation.body') }}</textarea>
                            <x-input-error :messages="$errors->get('translation.body')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="meta_title" :value="__('site.admin_meta_title')" />
                            <x-text-input id="meta_title" class="mt-1 block w-full" type="text" name="translation[meta_title]" :value="old('translation.meta_title')" />
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="meta_description" :value="__('site.admin_meta_description')" />
                            <x-text-input id="meta_description" class="mt-1 block w-full" type="text" name="translation[meta_description]" :value="old('translation.meta_description')" />
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="og_title" :value="__('site.admin_og_title')" />
                            <x-text-input id="og_title" class="mt-1 block w-full" type="text" name="translation[og_title]" :value="old('translation.og_title')" maxlength="255" />
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_title_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="og_description" :value="__('site.admin_og_description')" />
                            <x-text-input id="og_description" class="mt-1 block w-full" type="text" name="translation[og_description]" :value="old('translation.og_description')" maxlength="500" />
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_description_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="canonical_url" :value="__('site.admin_canonical_url')" />
                            <x-text-input id="canonical_url" class="mt-1 block w-full bg-gray-50 text-gray-500 dark:bg-gray-800/60 dark:text-gray-400" type="url" name="translation[canonical_url]" :value="old('translation.canonical_url')" maxlength="500" :placeholder="__('site.admin_placeholder_canonical_url')" readonly />
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">{{ __('site.admin_canonical_url_auto_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="og_image_url" :value="__('site.admin_og_image_url')" />
                            <x-text-input id="og_image_url" class="mt-1 block w-full bg-gray-50 text-gray-500 dark:bg-gray-800/60 dark:text-gray-400" type="url" name="translation[og_image_url]" :value="old('translation.og_image_url')" maxlength="500" :placeholder="__('site.admin_placeholder_og_image_url')" readonly />
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">{{ __('site.admin_og_image_url_auto_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                        @include('admin.partials.seo-robots-box', [
                            'namePrefix' => 'translation',
                            'noindexChecked' => old('translation.robots_noindex'),
                            'nofollowChecked' => old('translation.robots_nofollow'),
                        ])
                        </div>
                        <div data-admin-article-panel="seo">
                        <x-admin.seo-preview variant="article" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="sources_json" :value="__('site.admin_articles_sources_optional')" />
                            <textarea id="sources_json" name="translation[sources_json]" rows="4" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" placeholder="{{ __('site.admin_articles_sources_placeholder') }}">{{ old('translation.sources_json') }}</textarea>
                            <button type="button" class="mt-2 inline-flex items-center rounded border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" data-copy-sources-template="#sources_json" data-copy-label="{{ __('site.copy_json_template') }}" data-copied-label="{{ __('site.copied') }}" data-replace-confirm="{{ __('site.sources_json_replace_confirm') }}">{{ __('site.copy_json_template') }}</button>
                            <pre class="mt-2 overflow-x-auto rounded-md bg-gray-900 p-3 text-[11px] text-gray-100">[
  {
    "source_id": "S1",
    "url": "https://example.com/news/sample",
    "title": "Source title",
    "claim_note": "Short note for this claim"
  }
]</pre>
                        </div>
                </div>

                <div class="flex gap-3">
                    <x-primary-button>{{ __('site.common_save') }}</x-primary-button>
                    <x-admin.btn :href="route('admin.articles.index')">{{ __('site.common_cancel') }}</x-admin.btn>
                </div>
            </form>
        </div>
    </div>

    @once
        @push('scripts')
            @php
                $initialCreateTab = 'publishing';
                if ($errors->hasAny(['featured_image', 'featured_image_alt', 'featured_image_caption'])) {
                    $initialCreateTab = 'image';
                }
                if ($errors->hasAny(['translation.title', 'translation.slug', 'translation.excerpt', 'translation.body', 'translation.sources_json'])) {
                    $initialCreateTab = 'content';
                }
                if ($errors->hasAny(['translation.meta_title', 'translation.meta_description', 'translation.og_title', 'translation.og_description', 'translation.canonical_url', 'translation.og_image_url', 'translation.robots_noindex', 'translation.robots_nofollow'])) {
                    $initialCreateTab = 'seo';
                }
            @endphp
            <div id="article-create-import-config" hidden data-open-paste="{{ ($errors->has('json_text') || old('json_text')) ? '1' : '0' }}" data-initial-tab="{{ $initialCreateTab }}"></div>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
            <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
            @include('admin.partials.seo-preview-scripts')
            @include('admin.partials.article-canonical-sync', ['fixedLocale' => null])
            <script>
                var openImportPasteTab = (function () {
                    var el = document.getElementById('article-create-import-config');
                    return !!(el && el.getAttribute('data-open-paste') === '1');
                })();
                var initialCreateTab = (function () {
                    var el = document.getElementById('article-create-import-config');
                    return (el && el.getAttribute('data-initial-tab')) || 'publishing';
                })();
                function switchImportTab(tab) {
                    var fileEl = document.getElementById('tab-file');
                    var pasteEl = document.getElementById('tab-paste');
                    var fileBtn = document.getElementById('tab-file-btn');
                    var pasteBtn = document.getElementById('tab-paste-btn');
                    if (tab === 'paste') {
                        fileEl.classList.add('hidden');
                        pasteEl.classList.remove('hidden');
                        pasteBtn.classList.add('bg-white', 'text-gray-800', 'border-gray-200', 'dark:bg-gray-800', 'dark:text-gray-100', 'dark:border-gray-700');
                        pasteBtn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                        fileBtn.classList.remove('bg-white', 'text-gray-800', 'border-gray-200', 'dark:bg-gray-800', 'dark:text-gray-100', 'dark:border-gray-700');
                        fileBtn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    } else {
                        pasteEl.classList.add('hidden');
                        fileEl.classList.remove('hidden');
                        fileBtn.classList.add('bg-white', 'text-gray-800', 'border-gray-200', 'dark:bg-gray-800', 'dark:text-gray-100', 'dark:border-gray-700');
                        fileBtn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                        pasteBtn.classList.remove('bg-white', 'text-gray-800', 'border-gray-200', 'dark:bg-gray-800', 'dark:text-gray-100', 'dark:border-gray-700');
                        pasteBtn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    function initArticleTabs() {
                        const tabsWrap = document.querySelector('[data-admin-article-tabs]');
                        if (!tabsWrap) return;
                        const tabButtons = tabsWrap.querySelectorAll('[data-admin-article-tab]');
                        const panels = document.querySelectorAll('[data-admin-article-panel]');
                        const show = function (tab) {
                            tabButtons.forEach(function (btn) {
                                const active = btn.getAttribute('data-admin-article-tab') === tab;
                                btn.classList.toggle('is-active', active);
                            });
                            panels.forEach(function (panel) {
                                panel.classList.toggle('hidden', panel.getAttribute('data-admin-article-panel') !== tab);
                            });
                        };
                        tabButtons.forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var tab = btn.getAttribute('data-admin-article-tab');
                                show(tab);
                                if (tab === 'content' && typeof tinymce !== 'undefined') {
                                    setTimeout(function () {
                                        tinymce.editors.forEach(function (ed) { try { ed.refresh(); } catch (e) {} });
                                    }, 50);
                                }
                            });
                        });
                        show(initialCreateTab);
                        window.setAdminArticleTab = show;
                    }

                    function initUnsavedGuard() {
                        const form = document.querySelector('form[data-admin-article-form]');
                        if (!form) return;
                        let dirty = false;
                        form.querySelectorAll('input, textarea, select').forEach(function (el) {
                            el.addEventListener('change', function () { dirty = true; });
                            el.addEventListener('input', function () { dirty = true; });
                        });
                        form.addEventListener('submit', function () {
                            if (typeof tinymce !== 'undefined') {
                                tinymce.triggerSave();
                            }
                            dirty = false;
                        });
                        window.addEventListener('beforeunload', function (event) {
                            if (!dirty) return;
                            event.preventDefault();
                            event.returnValue = '';
                        });
                    }

                    initArticleTabs();
                    initUnsavedGuard();
                    if (openImportPasteTab) {
                        switchImportTab('paste');
                    }

                    window.initArticleSeoPreview();

                    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            const target = document.querySelector(btn.getAttribute('data-copy-target'));
                            if (!target) return;

                            const value = target.value || target.textContent || '';
                            if (!value) return;

                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                try {
                                    await navigator.clipboard.writeText(value);
                                } catch (e) {
                                    target.focus();
                                    if (target.select) target.select();
                                }
                            } else {
                                target.focus();
                                if (target.select) target.select();
                            }

                            const copyLabel = btn.getAttribute('data-copy-label') || 'Copy';
                            const copiedLabel = btn.getAttribute('data-copied-label') || 'Copied';
                            btn.textContent = copiedLabel;
                            btn.disabled = true;
                            setTimeout(function () {
                                btn.textContent = copyLabel;
                                btn.disabled = false;
                            }, 1200);
                        });
                    });

                    var TARGET_W = 1200;
                    var TARGET_H = 628;
                    var ASPECT = TARGET_W / TARGET_H;
                    var picker = document.getElementById('featured_image_picker');
                    var hiddenInput = document.getElementById('featured_image');
                    var modal = document.getElementById('cropper-modal');
                    var sourceImg = document.getElementById('cropper-source');
                    var applyBtn = document.getElementById('cropper-apply');
                    var cancelBtn = document.getElementById('cropper-cancel');
                    var cancelBtn2 = document.getElementById('cropper-cancel-btn');
                    var previewWrap = document.getElementById('crop-preview-wrap');
                    var previewImg = document.getElementById('crop-preview-img');
                    var cropDims = document.getElementById('crop-dims');
                    var cropperInstance = null;
                    var originalFile = null;

                    function openCropModal(file) {
                        originalFile = file;
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            sourceImg.src = e.target.result;
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                            if (cropperInstance) {
                                cropperInstance.destroy();
                            }
                            cropperInstance = new Cropper(sourceImg, {
                                aspectRatio: ASPECT,
                                viewMode: 1,
                                autoCropArea: 1,
                                movable: true,
                                zoomable: true,
                                scalable: false,
                                rotatable: false,
                            });
                        };
                        reader.readAsDataURL(file);
                    }

                    function closeCropModal() {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        if (cropperInstance) {
                            cropperInstance.destroy();
                            cropperInstance = null;
                        }
                        if (picker) picker.value = '';
                    }

                    function applyCrop() {
                        if (!cropperInstance) return;
                        var canvas = cropperInstance.getCroppedCanvas({
                            width: TARGET_W,
                            height: TARGET_H,
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });
                        canvas.toBlob(function (blob) {
                            var ext = (originalFile.name.split('.').pop() || 'jpg').toLowerCase();
                            var mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';
                            var fileName = originalFile.name.replace(/\.[^.]+$/, '') + '-cropped.' + (mimeType === 'image/png' ? 'png' : 'jpg');
                            var croppedFile = new File([blob], fileName, { type: mimeType });
                            var dt = new DataTransfer();
                            dt.items.add(croppedFile);
                            hiddenInput.files = dt.files;
                            previewImg.src = canvas.toDataURL(mimeType, 0.9);
                            cropDims.textContent = TARGET_W + '×' + TARGET_H + ' px — Google News compatible ✓';
                            previewWrap.classList.remove('hidden');
                            closeCropModal();
                        }, 'image/jpeg', 0.92);
                    }

                    if (picker && hiddenInput && modal && sourceImg && previewWrap && previewImg && cropDims && applyBtn && cancelBtn && cancelBtn2 && typeof Cropper !== 'undefined') {
                        picker.addEventListener('change', function () {
                            var file = this.files && this.files[0];
                            if (!file) return;
                            openCropModal(file);
                        });
                        applyBtn.addEventListener('click', applyCrop);
                        cancelBtn.addEventListener('click', closeCropModal);
                        cancelBtn2.addEventListener('click', closeCropModal);
                        modal.addEventListener('click', function (e) {
                            if (e.target === modal) closeCropModal();
                        });
                    }

                    document.querySelectorAll('[data-copy-sources-template]').forEach(function (btn) {
                        btn.addEventListener('click', async function () {
                            const target = document.querySelector(btn.getAttribute('data-copy-sources-template'));
                            if (!target) return;
                            const template = '[\n  {\n    "source_id": "S1",\n    "url": "https://example.com/news/sample",\n    "title": "Source title",\n    "claim_note": "Short note for this claim"\n  }\n]';
                            const hasContent = (target.value || '').trim() !== '';
                            if (hasContent && !window.confirm(btn.getAttribute('data-replace-confirm') || 'Replace existing content?')) {
                                return;
                            }
                            target.value = template;
                            target.dispatchEvent(new Event('input', { bubbles: true }));

                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                try {
                                    await navigator.clipboard.writeText(template);
                                } catch (e) {
                                    // Clipboard can fail on some browsers; textarea is still filled.
                                }
                            }

                            const copyLabel = btn.getAttribute('data-copy-label') || 'Copy';
                            const copiedLabel = btn.getAttribute('data-copied-label') || 'Copied';
                            btn.textContent = copiedLabel;
                            btn.disabled = true;
                            setTimeout(function () {
                                btn.textContent = copyLabel;
                                btn.disabled = false;
                            }, 1200);
                        });
                    });

                    if (typeof tinymce === 'undefined') return;
                    tinymce.init({
                        selector: '.tinymce-body',
                        height: 640,
                        min_height: 480,
                        menubar: false,
                        plugins: 'link lists code image',
                        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image | code removeformat',
                        toolbar_sticky: true,
                        toolbar_sticky_offset: 0,
                        branding: false,
                        convert_urls: false,
                        relative_urls: false,
                        content_style: 'body{margin:10px;font-family:ui-sans-serif,system-ui,sans-serif;font-size:16px;line-height:1.6;overflow-wrap:break-word;word-wrap:break-word}img,video,svg{max-width:100%!important;height:auto!important}',
                        setup: function (editor) {
                            editor.on('change input undo redo', function () {
                                editor.save();
                            });
                        },
                        images_upload_handler: function (blobInfo, progress) {
                            return new Promise(function (resolve, reject) {
                                const formData = new FormData();
                                formData.append('file', blobInfo.blob(), blobInfo.filename());
                                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                fetch('/admin/media/editor-upload', {
                                    method: 'POST',
                                    body: formData,
                                    credentials: 'same-origin',
                                })
                                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                                .then(function (data) { resolve(data.location); })
                                .catch(function () { reject('Image upload failed'); });
                            });
                        },
                    });
                });
            </script>
        @endpush
    @endonce
</x-app-layout>
