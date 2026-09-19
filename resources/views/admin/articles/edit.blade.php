<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_edit_article') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                @if($translation)
                    <x-admin.btn :href="route('admin.articles.preview', $article).'?locale='.$articleLocale" variant="info" target="_blank" rel="noopener noreferrer" class="hover:bg-indigo-500">{{ __('site.admin_open_preview') }}</x-admin.btn>
                @endif
                @if(($googleIndexing['feature_on'] ?? false) && ($googleIndexing['eligible'] ?? false))
                    @if(($googleIndexing['configured'] ?? false) && ($googleIndexing['remaining'] ?? 0) > 0 && ($googleIndexing['needs_notify'] ?? false))
                        <form method="post" action="{{ route('admin.articles.google-indexing', $article) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50 sm:text-sm"
                                    title="{{ __('site.admin_google_index_notify_hint') }}">
                                {{ __('site.admin_google_index_notify') }}
                            </button>
                        </form>
                    @elseif(!($googleIndexing['needs_notify'] ?? true))
                        <span class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                              title="{{ __('site.admin_google_index_wait_edit') }}">✓ {{ __('site.admin_google_index_notify') }}</span>
                    @else
                        <button type="button" disabled
                                class="cursor-not-allowed rounded-md border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                                title="{{ !($googleIndexing['configured'] ?? false) ? __('site.admin_google_index_not_configured') : __('site.admin_google_index_limit', ['limit' => $googleIndexing['limit'] ?? 0]) }}">
                            {{ __('site.admin_google_index_notify') }}
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            <div class="mb-6 space-y-3 rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-sm text-gray-800 dark:border-indigo-800 dark:bg-indigo-500/10 dark:text-gray-200">
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ __('site.admin_preview') }}</p>
                <p class="text-gray-700 dark:text-gray-300">{{ __('site.language') }}: <span class="font-mono font-semibold uppercase">{{ $articleLocale }}</span></p>
                @if($translation)
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.articles.preview', $article).'?locale='.$articleLocale }}" target="_blank" rel="noopener noreferrer" class="rounded bg-white px-3 py-1 text-indigo-800 shadow-sm ring-1 ring-indigo-200 hover:bg-indigo-100 dark:bg-gray-800 dark:text-indigo-300 dark:ring-indigo-700 dark:hover:bg-indigo-500/20">{{ strtoupper($articleLocale) }} (admin)</a>
                    </div>
                @endif
                @if(!empty($signedPreviewUrls))
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('site.admin_shareable_signed_links') }}</p>
                    <ul class="space-y-2 font-mono text-xs text-gray-700 dark:text-gray-300">
                        @foreach($signedPreviewUrls as $loc => $url)
                            <li class="break-all"><span class="font-sans font-semibold uppercase text-gray-600 dark:text-gray-400">{{ $loc }}</span>: <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-700 underline dark:text-indigo-300">{{ $url }}</a></li>
                        @endforeach
                    </ul>
                @endif
                {{-- publicUrl() boş locale ile app()->getLocale() kullanır; admin genelde en → TR makalede link kaybolur. --}}
                @php
                    $article->loadMissing('translations');
                    $publishLinks = $article->translations
                        ->filter(fn ($t) => filled($t->slug))
                        ->map(fn ($t) => ['locale' => $t->locale, 'url' => $article->publicUrl($t->locale)])
                        ->filter(fn ($row) => filled($row['url']))
                        ->unique('url')
                        ->values();
                @endphp
                @if($publishLinks->isNotEmpty())
                    <div class="border-t border-indigo-100 pt-3 dark:border-indigo-800">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('site.admin_articles_publish_link') }}</p>
                        <ul class="mt-2 space-y-2 font-mono text-xs text-gray-700 dark:text-gray-300">
                            @foreach($publishLinks as $row)
                                <li class="break-all">
                                    <span class="font-sans font-semibold uppercase text-gray-600 dark:text-gray-400">{{ $row['locale'] }}</span>:
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer" class="text-green-700 underline hover:text-green-900 dark:text-green-300 dark:hover:text-green-200">{{ $row['url'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <form method="post" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data" class="admin-form-card" data-admin-article-form novalidate>
                @csrf
                @method('put')
                <div class="sticky top-2 z-20 -mx-4 rounded-lg border border-gray-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-700 dark:bg-gray-900/95">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap gap-2" data-admin-article-tabs>
                            <button type="button" class="admin-tab-btn is-active" data-admin-article-tab="publishing">Yayin</button>
                            <button type="button" class="admin-tab-btn" data-admin-article-tab="image">Gorsel</button>
                            <button type="button" class="admin-tab-btn" data-admin-article-tab="content">Icerik</button>
                            <button type="button" class="admin-tab-btn" data-admin-article-tab="seo">SEO</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-primary-button>{{ __('site.common_update') }}</x-primary-button>
                            <x-admin.btn :href="route('admin.articles.index')">{{ __('site.common_cancel') }}</x-admin.btn>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 pt-2 sm:grid-cols-2" data-admin-article-panel="publishing">
                    <div>
                        <x-input-label for="category_id" :value="__('site.common_category')" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" required>
                            @foreach($categories as $cat)
                                @php $lab = $cat->translate('en')?->name ?? $cat->key; @endphp
                                <option value="{{ $cat->id }}" @selected(old('category_id', $article->category_id) == $cat->id)>{{ $lab }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="locale" :value="__('site.language')" />
                        <select id="locale" name="locale" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" required>
                            @foreach(config('novaranews.locales') as $l)
                                <option value="{{ $l }}" @selected(old('locale', $articleLocale) === $l)>{{ strtoupper($l) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('site.admin_locale_change_hint') }}</p>
                        <x-input-error :messages="$errors->get('locale')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="status" :value="__('site.admin_status_label')" />
                        <select id="status" name="status" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="draft" @selected(old('status', $article->status) === 'draft')>{{ __('site.admin_draft') }}</option>
                            <option value="ai_ready" @selected(old('status', $article->status) === 'ai_ready')>{{ __('site.admin_ai_ready') }}</option>
                            <option value="editor_reviewed" @selected(old('status', $article->status) === 'editor_reviewed')>{{ __('site.admin_editor_reviewed') }}</option>
                            <option value="published" @selected(old('status', $article->status) === 'published')>{{ __('site.admin_published') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>
                <div data-admin-article-panel="publishing">
                    <x-input-label for="published_at" :value="__('site.admin_published_at')" />
                    <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full" :value="old('published_at', $article->published_at?->format('Y-m-d\TH:i'))" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_leave_empty_now_hint') }}</p>
                    <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
                </div>
                    <div class="space-y-2" data-admin-article-panel="publishing">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                            <input type="checkbox" name="is_breaking" value="1" class="rounded border-gray-300" @checked(old('is_breaking', $article->is_breaking))>
                            {{ __('site.breaking_checkbox_help') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                            <input type="checkbox" name="is_editors_pick" value="1" class="rounded border-gray-300" @checked(old('is_editors_pick', $article->is_editors_pick))>
                            {{ __('site.editors_pick_checkbox_help') }}
                        </label>
                    </div>
                    <div data-admin-article-panel="publishing">
                        <x-input-label for="content_type" :value="__('site.admin_content_type')" />
                        <select id="content_type" name="content_type" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                            @foreach(\App\Models\Article::CONTENT_TYPES as $ct)
                                <option value="{{ $ct }}" @selected(old('content_type', $article->contentTypeKey()) === $ct)>{{ __('site.content_type_'.$ct) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_content_type_help') }}</p>
                        <x-input-error :messages="$errors->get('content_type')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2" data-admin-article-panel="publishing">
                        <x-input-label for="user_id" :value="__('site.common_author')" />
                        <select id="user_id" name="user_id" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                            <option value="">{{ __('site.admin_author_not_set') }}</option>
                            @foreach($authors as $a)
                                <option
                                    value="{{ $a->id }}"
                                    data-has-slug="{{ filled($a->slug) ? '1' : '0' }}"
                                    data-has-title="{{ filled($a->title) ? '1' : '0' }}"
                                    data-bio-words="{{ $a->profileBioWordCountForDefaultLocale() }}"
                                    @selected((int) old('user_id', $article->user_id) === $a->id)
                                >{{ $a->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_author_when_published') }}</p>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>
                @if($article->featured_image)
                    <div data-admin-article-panel="image">
                    @php
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
                        $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
                        $currentImageUrl = url($publicDisk->url($article->featured_image));
                    @endphp
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('site.admin_current_image') }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <x-text-input
                                id="current_featured_image_url"
                                type="text"
                                readonly
                                :value="$currentImageUrl"
                                class="w-full flex-1 min-w-[18rem] bg-gray-50 text-gray-700 dark:bg-gray-800/70 dark:text-gray-200"
                            />
                            <button
                                type="button"
                                class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                data-copy-target="#current_featured_image_url"
                                data-copy-label="{{ __('site.admin_media_copy_url') }}"
                                data-copied-label="{{ __('site.copied') }}"
                            >
                                {{ __('site.admin_media_copy_url') }}
                            </button>
                        </div>
                        <div class="mt-3 overflow-hidden rounded border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                            <img
                                src="{{ $currentImageUrl }}"
                                alt="{{ $article->featured_image_alt ?: $translation?->title ?: __('site.admin_current_image') }}"
                                class="max-h-64 w-full object-contain object-center"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                    </div>
                    </div>
                @endif
                <div data-admin-article-panel="image">
                    <x-input-label for="featured_image_picker" :value="__('site.admin_replace_featured_image')" />
                    <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Google News: min 1200×628 px · 16:9 oran önerilir</p>
                    <input id="featured_image_picker" type="file" accept="image/*" class="mt-1 block w-full text-sm" />
                    {{-- Hidden input that carries the cropped blob to the server --}}
                    <input id="featured_image" name="featured_image" type="file" class="hidden" />
                    <x-input-error :messages="$errors->get('featured_image')" class="mt-2" />

                    {{-- Crop preview thumbnail --}}
                    <div id="crop-preview-wrap" class="mt-2 hidden">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kırpma önizlemesi:</p>
                        <img id="crop-preview-img" src="" alt="Kırpma önizlemesi"
                            class="mt-1 h-24 w-auto rounded border border-gray-300 dark:border-gray-600" />
                        <p id="crop-dims" class="mt-1 text-xs text-green-600 dark:text-green-400"></p>
                    </div>
                </div>

                {{-- Cropper Modal --}}
                <div id="cropper-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
                    <div class="flex w-full max-w-3xl flex-col rounded-lg bg-white shadow-2xl dark:bg-gray-900">
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Görseli Kırp — Google News (1200×628 · 16:9)</h3>
                            <button type="button" id="cropper-cancel" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                        </div>
                        <div class="max-h-[60vh] overflow-hidden bg-gray-100 dark:bg-gray-800">
                            <img id="cropper-source" src="" alt="" class="block max-w-full" />
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Kırpma alanını sürükleyip yeniden boyutlandırabilirsiniz.</span>
                            <div class="flex gap-2">
                                <button type="button" id="cropper-cancel-btn"
                                    class="rounded border border-gray-300 bg-white px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                    İptal
                                </button>
                                <button type="button" id="cropper-apply"
                                    class="rounded bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Kırpmayı Uygula
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div data-admin-article-panel="image">
                    <x-input-label for="featured_image_alt" :value="__('site.admin_featured_image_alt')" />
                    <x-text-input id="featured_image_alt" name="featured_image_alt" type="text" class="mt-1 block w-full" :value="old('featured_image_alt', $article->featured_image_alt)" />
                    <x-input-error :messages="$errors->get('featured_image_alt')" class="mt-2" />
                </div>
                <div data-admin-article-panel="image">
                    <x-input-label for="featured_image_caption" :value="__('site.admin_featured_image_caption_optional')" />
                    <x-text-input id="featured_image_caption" name="featured_image_caption" type="text" class="mt-1 block w-full" :value="old('featured_image_caption', $article->featured_image_caption)" />
                    <x-input-error :messages="$errors->get('featured_image_caption')" class="mt-2" />
                </div>

                <div class="space-y-3">
                        <div data-admin-article-panel="content">
                            <x-input-label for="title" :value="__('site.admin_title_label')" />
                            <x-text-input id="title" class="mt-1 block w-full" type="text" name="translation[title]" :value="old('translation.title', $translation?->title)" />
                            <x-input-error :messages="$errors->get('translation.title')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="slug" :value="__('site.admin_url_slug_label')" />
                            <x-text-input id="slug" class="mt-1 block w-full font-mono text-sm" type="text" name="translation[slug]" :value="old('translation.slug', $translation?->slug)" />
                            <x-input-error :messages="$errors->get('translation.slug')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="excerpt" :value="__('site.admin_excerpt_label')" />
                            <textarea id="excerpt" name="translation[excerpt]" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">{{ old('translation.excerpt', $translation?->excerpt) }}</textarea>
                            <x-input-error :messages="$errors->get('translation.excerpt')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="body" :value="__('site.admin_body_html_label')" />
                            <textarea id="body" name="translation[body]" rows="10" class="tinymce-body mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">{{ old('translation.body', $translation?->body) }}</textarea>
                            <x-input-error :messages="$errors->get('translation.body')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="meta_title" :value="__('site.admin_meta_title')" />
                            <x-text-input id="meta_title" class="mt-1 block w-full" type="text" name="translation[meta_title]" :value="old('translation.meta_title', $translation?->meta_title)" />
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="meta_description" :value="__('site.admin_meta_description')" />
                            <x-text-input id="meta_description" class="mt-1 block w-full" type="text" name="translation[meta_description]" :value="old('translation.meta_description', $translation?->meta_description)" />
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="og_title" :value="__('site.admin_og_title')" />
                            <x-text-input id="og_title" class="mt-1 block w-full" type="text" name="translation[og_title]" :value="old('translation.og_title', $translation?->og_title)" maxlength="255" />
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_title_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="og_description" :value="__('site.admin_og_description')" />
                            <x-text-input id="og_description" class="mt-1 block w-full" type="text" name="translation[og_description]" :value="old('translation.og_description', $translation?->og_description)" maxlength="500" />
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_description_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="canonical_url" :value="__('site.admin_canonical_url')" />
                            @php
                                $effectiveContentType = old('content_type', $article->contentTypeKey());
                                $slugForCanonical = old('translation.slug', $translation?->slug);
                                $autoCanonicalUrl = filled($slugForCanonical)
                                    ? route('article.show', ['locale' => $articleLocale, 'articlePrefix' => article_path_segment($articleLocale, $effectiveContentType), 'articleSlug' => $slugForCanonical])
                                    : null;
                            @endphp
                            <x-text-input id="canonical_url" class="mt-1 block w-full bg-gray-50 text-gray-500 dark:bg-gray-800/60 dark:text-gray-400" type="url" name="translation[canonical_url]" :value="old('translation.canonical_url', $autoCanonicalUrl)" maxlength="500" :placeholder="__('site.admin_placeholder_canonical_url')" readonly />
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">{{ __('site.admin_canonical_url_auto_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="og_image_url" :value="__('site.admin_og_image_url')" />
                            @php
                                $autoOgImageUrl = null;
                                if ($article->featured_image) {
                                    /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
                                    $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
                                    $autoOgImageUrl = url($publicDisk->url($article->featured_image));
                                }
                            @endphp
                            <x-text-input id="og_image_url" class="mt-1 block w-full bg-gray-50 text-gray-500 dark:bg-gray-800/60 dark:text-gray-400" type="url" name="translation[og_image_url]" :value="old('translation.og_image_url', $autoOgImageUrl)" maxlength="500" :placeholder="__('site.admin_placeholder_og_image_url')" readonly />
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">{{ __('site.admin_og_image_url_auto_hint') }}</p>
                        </div>
                        <div data-admin-article-panel="seo">
                            <x-input-label for="hreflang_group" value="Hreflang Grubu" />
                            <x-text-input id="hreflang_group" name="hreflang_group" type="text" class="mt-1 block w-full font-mono"
                                :value="old('hreflang_group', $article->hreflang_group)"
                                placeholder="örn: ai-job-2026" maxlength="100" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aynı grubu paylaşan farklı dillerdeki makaleler otomatik olarak hreflang ile birbirine bağlanır.</p>
                            <x-input-error :messages="$errors->get('hreflang_group')" class="mt-2" />
                        </div>
                        <div data-admin-article-panel="seo">
                        @include('admin.partials.seo-robots-box', [
                            'namePrefix' => 'translation',
                            'noindexChecked' => old('translation.robots_noindex', $translation?->robots_noindex),
                            'nofollowChecked' => old('translation.robots_nofollow', $translation?->robots_nofollow),
                        ])
                        </div>
                        <div data-admin-article-panel="seo">
                        <x-admin.seo-preview variant="article" :article-readiness="$articleReadiness" />
                        </div>
                        <div data-admin-article-panel="content">
                            <x-input-label for="sources_json" :value="__('site.admin_articles_sources_optional')" />
                            @php
                                $sourcesValue = old('translation.sources_json', '');
                                if ($sourcesValue === '' && $translation && is_array($translation->sources) && $translation->sources !== []) {
                                    $sourcesValue = json_encode($translation->sources, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }
                            @endphp
                            <textarea id="sources_json" name="translation[sources_json]" rows="4" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" placeholder="{{ __('site.admin_articles_sources_placeholder') }}">{{ $sourcesValue }}</textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_articles_sources_hint') }}</p>
                        </div>
                </div>

                <div class="flex gap-3">
                    <x-primary-button>{{ __('site.common_update') }}</x-primary-button>
                    <x-admin.btn :href="route('admin.articles.index')">{{ __('site.common_cancel') }}</x-admin.btn>
                </div>
            </form>
        </div>
    </div>

    @once
        @push('scripts')
            @php
                $initialEditTab = 'publishing';
                if ($errors->hasAny(['featured_image', 'featured_image_alt', 'featured_image_caption'])) {
                    $initialEditTab = 'image';
                }
                if ($errors->hasAny(['translation.title', 'translation.slug', 'translation.excerpt', 'translation.body', 'translation.sources_json'])) {
                    $initialEditTab = 'content';
                }
                if ($errors->hasAny(['translation.meta_title', 'translation.meta_description', 'translation.og_title', 'translation.og_description', 'translation.canonical_url', 'translation.og_image_url', 'translation.robots_noindex', 'translation.robots_nofollow'])) {
                    $initialEditTab = 'seo';
                }
            @endphp
            <div id="article-edit-tab-config" hidden data-initial-tab="{{ $initialEditTab }}"></div>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
            <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
            @include('admin.partials.seo-preview-scripts')
            @include('admin.partials.article-canonical-sync', ['fixedLocale' => $articleLocale])
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const editTabConfigEl = document.getElementById('article-edit-tab-config');
                    const initialEditTab = editTabConfigEl ? (editTabConfigEl.getAttribute('data-initial-tab') || 'publishing') : 'publishing';
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
                                const tab = btn.getAttribute('data-admin-article-tab');
                                show(tab);
                                if (tab === 'content' && typeof tinymce !== 'undefined') {
                                    setTimeout(function () {
                                        tinymce.editors.forEach(function (ed) { try { ed.refresh(); } catch (e) {} });
                                    }, 50);
                                }
                            });
                        });
                        show(initialEditTab);
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
                    window.initArticleSeoPreview();

                    if (typeof tinymce !== 'undefined') {
                        tinymce.init({
                            selector: '.tinymce-body',
                            height: 640,
                            min_height: 480,
                            menubar: false,
                            /* autoresize kaldırıldı: iframe içinde kaydırma olması için; aksi halde toolbar_sticky çalışmaz */
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
                                    .catch(function () { reject('Görsel yüklenemedi'); });
                                });
                            },
                        });
                    }

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

                    // ── Cropper.js integration ────────────────────────────────
                    const TARGET_W = 1200;
                    const TARGET_H = 628;
                    const ASPECT   = TARGET_W / TARGET_H; // 16:9 ≈ 1.912

                    const picker      = document.getElementById('featured_image_picker');
                    const hiddenInput = document.getElementById('featured_image');
                    const modal       = document.getElementById('cropper-modal');
                    const sourceImg   = document.getElementById('cropper-source');
                    const applyBtn    = document.getElementById('cropper-apply');
                    const cancelBtn   = document.getElementById('cropper-cancel');
                    const cancelBtn2  = document.getElementById('cropper-cancel-btn');
                    const previewWrap = document.getElementById('crop-preview-wrap');
                    const previewImg  = document.getElementById('crop-preview-img');
                    const cropDims    = document.getElementById('crop-dims');

                    let cropperInstance = null;
                    let originalFile    = null;

                    function openModal(file) {
                        originalFile = file;
                        const reader = new FileReader();
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

                    function closeModal() {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        if (cropperInstance) {
                            cropperInstance.destroy();
                            cropperInstance = null;
                        }
                        picker.value = '';
                    }

                    function applyAndClose() {
                        if (!cropperInstance) return;

                        const canvas = cropperInstance.getCroppedCanvas({
                            width: TARGET_W,
                            height: TARGET_H,
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });

                        canvas.toBlob(function (blob) {
                            // Build a fake FileList-compatible transfer for the hidden input
                            const ext      = (originalFile.name.split('.').pop() || 'jpg').toLowerCase();
                            const mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';
                            const fileName = originalFile.name.replace(/\.[^.]+$/, '') + '-cropped.' + (mimeType === 'image/png' ? 'png' : 'jpg');
                            const croppedFile = new File([blob], fileName, { type: mimeType });

                            const dt = new DataTransfer();
                            dt.items.add(croppedFile);
                            hiddenInput.files = dt.files;

                            // Show preview thumbnail
                            previewImg.src = canvas.toDataURL(mimeType, 0.9);
                            cropDims.textContent = TARGET_W + '×' + TARGET_H + ' px — Google News uyumlu ✓';
                            previewWrap.classList.remove('hidden');

                            closeModal();
                        }, 'image/jpeg', 0.92);
                    }

                    picker.addEventListener('change', function () {
                        const file = this.files && this.files[0];
                        if (!file) return;
                        openModal(file);
                    });

                    applyBtn.addEventListener('click', applyAndClose);
                    cancelBtn.addEventListener('click', closeModal);
                    cancelBtn2.addEventListener('click', closeModal);
                    modal.addEventListener('click', function (e) {
                        if (e.target === modal) closeModal();
                    });
                });
            </script>
        @endpush
    @endonce
</x-app-layout>
