<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
            {{ __('site.admin_static_page_edit', ['page' => $staticPage->key]) }}
        </h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            @if($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    {{ __('site.admin_fix_highlighted_errors') }}
                    <ul class="mt-1 list-disc pl-5 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('admin.static-pages.update', $staticPage) }}" class="admin-form-card">
                @csrf
                @method('PUT')
                @php
                    $initialLocale = old('active_locale', config('novaranews.default_locale', $locales[0] ?? 'en'));
                    foreach ($locales as $l) {
                        if ($errors->hasAny([
                            'translations.'.$l.'.title',
                            'translations.'.$l.'.slug',
                            'translations.'.$l.'.meta_title',
                            'translations.'.$l.'.meta_description',
                            'translations.'.$l.'.og_title',
                            'translations.'.$l.'.og_description',
                            'translations.'.$l.'.canonical_url',
                            'translations.'.$l.'.og_image_url',
                            'translations.'.$l.'.robots_noindex',
                            'translations.'.$l.'.robots_nofollow',
                            'translations.'.$l.'.content',
                        ])) {
                            $initialLocale = $l;
                            break;
                        }
                    }
                @endphp
                <input type="hidden" name="active_locale" id="active_locale" value="{{ $initialLocale }}">

                <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-700" data-static-page-locale-tabs>
                    @foreach($locales as $loc)
                        <button type="button" class="admin-tab-btn" data-static-page-locale-tab="{{ $loc }}">
                            {{ strtoupper($loc) }}
                            <span class="ml-1 hidden rounded-full px-1.5 py-0.5 text-[10px] font-bold" data-static-page-locale-missing="{{ $loc }}">0</span>
                        </button>
                    @endforeach
                </div>

                @foreach($locales as $loc)
                    @php
                        $tr          = $translations->get($loc);
                        $defaultSlug = $tr?->slug ?? $staticPage->key;
                        $currentSlug = old("translations.{$loc}.slug", $defaultSlug);
                        $previewUrl  = url("/{$loc}/{$currentSlug}");
                        $currentTitle = old("translations.{$loc}.title", $tr?->title);
                    @endphp
                    <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-static-page-locale-panel="{{ $loc }}">
                        <legend class="rounded bg-gray-700 px-2 py-0.5 text-sm font-bold text-white dark:bg-gray-600">{{ strtoupper($loc) }}</legend>

                        <div class="mt-4 space-y-4">

                            {{-- TITLE --}}
                            <div>
                                <x-input-label :for="'title_'.$loc" :value="__('site.admin_static_page_title_label')" />
                                <x-text-input
                                    :id="'title_'.$loc"
                                    class="mt-1 block w-full max-w-xl"
                                    type="text"
                                    :name="'translations['.$loc.'][title]'"
                                    :value="$currentTitle"
                                    maxlength="255"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_static_page_title_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.title')" class="mt-1" />
                            </div>

                            {{-- SLUG --}}
                            <div>
                                <x-input-label :for="'slug_'.$loc" :value="__('site.admin_static_page_url_slug')" />
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="rounded-l border border-r-0 border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        /{{ $loc }}/
                                    </span>
                                    <x-text-input
                                        :id="'slug_'.$loc"
                                        class="block w-48 rounded-l-none"
                                        type="text"
                                        :name="'translations['.$loc.'][slug]'"
                                        :value="$currentSlug"
                                        placeholder="{{ $defaultSlug }}"
                                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                    />
                                    <a href="{{ $previewUrl }}" target="_blank"
                                       class="inline-flex items-center gap-1 rounded border border-gray-300 bg-white px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        {{ __('site.admin_static_page_preview') }}
                                    </a>
                                </div>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('site.admin_static_page_slug_hint', ['slug' => $defaultSlug]) }}
                                </p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.slug')" class="mt-1" />
                            </div>

                            {{-- META DESCRIPTION --}}
                            <div>
                                <x-input-label :for="'meta_title_'.$loc" :value="__('site.admin_meta_title')" />
                                <x-text-input
                                    :id="'meta_title_'.$loc"
                                    class="mt-1 block w-full"
                                    type="text"
                                    :name="'translations['.$loc.'][meta_title]'"
                                    :value="old('translations.'.$loc.'.meta_title', $tr?->meta_title)"
                                    maxlength="255"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_meta_title_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.meta_title')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'meta_description_'.$loc" :value="__('site.admin_meta_description')" />
                                <x-text-input
                                    :id="'meta_description_'.$loc"
                                    class="mt-1 block w-full"
                                    type="text"
                                    :name="'translations['.$loc.'][meta_description]'"
                                    :value="old('translations.'.$loc.'.meta_description', $tr?->meta_description)"
                                    maxlength="500"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_static_page_meta_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.meta_description')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'og_title_'.$loc" :value="__('site.admin_og_title')" />
                                <x-text-input
                                    :id="'og_title_'.$loc"
                                    class="mt-1 block w-full"
                                    type="text"
                                    :name="'translations['.$loc.'][og_title]'"
                                    :value="old('translations.'.$loc.'.og_title', $tr?->og_title)"
                                    maxlength="255"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_title_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.og_title')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'og_description_'.$loc" :value="__('site.admin_og_description')" />
                                <x-text-input
                                    :id="'og_description_'.$loc"
                                    class="mt-1 block w-full"
                                    type="text"
                                    :name="'translations['.$loc.'][og_description]'"
                                    :value="old('translations.'.$loc.'.og_description', $tr?->og_description)"
                                    maxlength="500"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_description_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.og_description')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'canonical_url_'.$loc" :value="__('site.admin_canonical_url')" />
                                <x-text-input
                                    :id="'canonical_url_'.$loc"
                                    class="mt-1 block w-full"
                                    type="url"
                                    :name="'translations['.$loc.'][canonical_url]'"
                                    :value="old('translations.'.$loc.'.canonical_url', $tr?->canonical_url)"
                                    maxlength="500"
                                    placeholder="{{ __('site.admin_placeholder_canonical_url') }}"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_canonical_url_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.canonical_url')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :for="'og_image_url_'.$loc" :value="__('site.admin_og_image_url')" />
                                <x-text-input
                                    :id="'og_image_url_'.$loc"
                                    class="mt-1 block w-full"
                                    type="url"
                                    :name="'translations['.$loc.'][og_image_url]'"
                                    :value="old('translations.'.$loc.'.og_image_url', $tr?->og_image_url)"
                                    maxlength="500"
                                    placeholder="{{ __('site.admin_placeholder_og_image_url') }}"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_og_image_url_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.og_image_url')" class="mt-2" />
                            </div>

                            @include('admin.partials.seo-robots-box', [
                                'namePrefix' => 'translations['.$loc.']',
                                'noindexChecked' => old('translations.'.$loc.'.robots_noindex', $tr?->robots_noindex),
                                'nofollowChecked' => old('translations.'.$loc.'.robots_nofollow', $tr?->robots_nofollow),
                                'errorNoindex' => $errors->get('translations.'.$loc.'.robots_noindex'),
                                'errorNofollow' => $errors->get('translations.'.$loc.'.robots_nofollow'),
                            ])
                            <x-admin.seo-preview variant="generic" />

                            {{-- CONTENT --}}
                            <div>
                                <x-input-label :for="'content_'.$loc" :value="__('site.admin_static_page_content_label')" />
                                <textarea
                                    id="content_{{ $loc }}"
                                    name="translations[{{ $loc }}][content]"
                                    rows="14"
                                    class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                >{{ old('translations.'.$loc.'.content', $tr?->content) }}</textarea>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_static_page_content_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.content')" class="mt-2" />
                            </div>

                        </div>
                    </fieldset>
                @endforeach

                <div class="flex gap-3">
                    <x-primary-button>{{ __('site.common_save') }}</x-primary-button>
                    <x-admin.btn :href="route('admin.static-pages.index')">{{ __('site.common_cancel') }}</x-admin.btn>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        @include('admin.partials.seo-preview-scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                (function initStaticPageLocaleTabs() {
                    const tabsWrap = document.querySelector('[data-static-page-locale-tabs]');
                    if (!tabsWrap) return;
                    const tabButtons = tabsWrap.querySelectorAll('[data-static-page-locale-tab]');
                    const panels = document.querySelectorAll('[data-static-page-locale-panel]');
                    const activeLocaleInput = document.getElementById('active_locale');
                    const initialLocale = (activeLocaleInput && activeLocaleInput.value) || '{{ $initialLocale }}';
                    const requiredFields = ['title', 'slug', 'content'];

                    const show = function (locale) {
                        tabButtons.forEach(function (btn) {
                            btn.classList.toggle('is-active', btn.getAttribute('data-static-page-locale-tab') === locale);
                        });
                        panels.forEach(function (panel) {
                            panel.classList.toggle('hidden', panel.getAttribute('data-static-page-locale-panel') !== locale);
                        });
                        if (activeLocaleInput) {
                            activeLocaleInput.value = locale;
                        }
                    };

                    const updateMissingBadges = function () {
                        tabButtons.forEach(function (btn) {
                            const locale = btn.getAttribute('data-static-page-locale-tab');
                            const badge = btn.querySelector('[data-static-page-locale-missing="' + locale + '"]');
                            if (!badge) return;
                            let missing = 0;
                            requiredFields.forEach(function (field) {
                                const input = document.querySelector('[name="translations[' + locale + '][' + field + ']"]');
                                if (!input || !String(input.value || '').trim()) {
                                    missing++;
                                }
                            });
                            badge.textContent = String(missing);
                            badge.classList.toggle('hidden', missing === 0);
                            badge.classList.remove('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-500/20', 'dark:text-emerald-300');
                            badge.classList.remove('bg-amber-100', 'text-amber-700', 'dark:bg-amber-500/20', 'dark:text-amber-300');
                            badge.classList.remove('bg-rose-100', 'text-rose-700', 'dark:bg-rose-500/20', 'dark:text-rose-300');
                            if (missing === 0) {
                                badge.classList.add('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-500/20', 'dark:text-emerald-300');
                            } else if (missing === 1) {
                                badge.classList.add('bg-amber-100', 'text-amber-700', 'dark:bg-amber-500/20', 'dark:text-amber-300');
                            } else {
                                badge.classList.add('bg-rose-100', 'text-rose-700', 'dark:bg-rose-500/20', 'dark:text-rose-300');
                            }
                        });
                    };

                    tabButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            show(btn.getAttribute('data-static-page-locale-tab'));
                        });
                    });

                    document.querySelectorAll('[data-static-page-locale-panel] input, [data-static-page-locale-panel] textarea, [data-static-page-locale-panel] select')
                        .forEach(function (el) {
                            el.addEventListener('input', updateMissingBadges);
                            el.addEventListener('change', updateMissingBadges);
                        });

                    show(initialLocale);
                    updateMissingBadges();
                })();
                window.initGenericSeoPreview({ titleField: 'title' });
            });
        </script>
    @endpush
</x-app-layout>
