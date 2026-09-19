<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('site.admin_new_category') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            <form method="post" action="{{ route('admin.categories.store') }}" class="admin-form-card">
                @csrf
                @php
                    $initialLocale = old('active_locale', config('novaranews.default_locale', $locales[0] ?? 'en'));
                    foreach ($locales as $l) {
                        if ($errors->hasAny([
                            'translations.'.$l.'.name',
                            'translations.'.$l.'.slug',
                            'translations.'.$l.'.intro',
                            'translations.'.$l.'.meta_title',
                            'translations.'.$l.'.meta_description',
                            'translations.'.$l.'.og_title',
                            'translations.'.$l.'.og_description',
                            'translations.'.$l.'.canonical_url',
                            'translations.'.$l.'.og_image_url',
                            'translations.'.$l.'.robots_noindex',
                            'translations.'.$l.'.robots_nofollow',
                        ])) {
                            $initialLocale = $l;
                            break;
                        }
                    }
                @endphp
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="key" :value="__('site.admin_internal_key_extended')" />
                        <x-text-input id="key" name="key" type="text" class="mt-1 block w-full font-mono" :value="old('key')" required />
                        <x-input-error :messages="$errors->get('key')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sort_order" :value="__('site.admin_sort_order')" />
                        <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', 0)" />
                        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
                    </div>
                </div>

                <input type="hidden" name="active_locale" id="active_locale" value="{{ $initialLocale }}">

                <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-700" data-category-locale-tabs>
                    @foreach($locales as $loc)
                        <button type="button" class="admin-tab-btn" data-category-locale-tab="{{ $loc }}">
                            {{ strtoupper($loc) }}
                            <span class="ml-1 hidden rounded-full px-1.5 py-0.5 text-[10px] font-bold" data-category-locale-missing="{{ $loc }}">0</span>
                        </button>
                    @endforeach
                </div>

                @foreach($locales as $loc)
                    <fieldset class="rounded-lg border border-gray-200 p-4" data-category-locale-panel="{{ $loc }}">
                        <legend class="px-1 text-sm font-semibold text-gray-700">{{ strtoupper($loc) }}</legend>
                        <div class="mt-3 space-y-3">
                            <div>
                                <x-input-label :for="'name_'.$loc" :value="__('site.admin_name_label')" />
                                <x-text-input :id="'name_'.$loc" class="mt-1 block w-full" type="text" :name="'translations['.$loc.'][name]'" :value="old('translations.'.$loc.'.name')" required />
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'slug_'.$loc" :value="__('site.admin_url_slug_label')" />
                                <x-text-input :id="'slug_'.$loc" class="mt-1 block w-full font-mono text-sm" type="text" :name="'translations['.$loc.'][slug]'" :value="old('translations.'.$loc.'.slug')" required />
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.slug')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'intro_'.$loc" :value="__('site.admin_category_intro_label')" />
                                <x-text-input :id="'intro_'.$loc" class="mt-1 block w-full" type="text" :name="'translations['.$loc.'][intro]'" :value="old('translations.'.$loc.'.intro')" maxlength="500" />
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.intro')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'meta_title_'.$loc" :value="__('site.admin_meta_title')" />
                                <x-text-input :id="'meta_title_'.$loc" class="mt-1 block w-full" type="text" :name="'translations['.$loc.'][meta_title]'" :value="old('translations.'.$loc.'.meta_title')" maxlength="255" />
                                <p class="mt-1 text-xs text-gray-400">{{ __('site.admin_meta_title_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.meta_title')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'meta_description_'.$loc" :value="__('site.admin_meta_description')" />
                                <x-text-input :id="'meta_description_'.$loc" class="mt-1 block w-full" type="text" :name="'translations['.$loc.'][meta_description]'" :value="old('translations.'.$loc.'.meta_description')" maxlength="500" />
                                <p class="mt-1 text-xs text-gray-400">{{ __('site.admin_meta_description_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.meta_description')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'og_title_'.$loc" :value="__('site.admin_og_title')" />
                                <x-text-input :id="'og_title_'.$loc" class="mt-1 block w-full" type="text" :name="'translations['.$loc.'][og_title]'" :value="old('translations.'.$loc.'.og_title')" maxlength="255" />
                                <p class="mt-1 text-xs text-gray-400">{{ __('site.admin_og_title_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.og_title')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'og_description_'.$loc" :value="__('site.admin_og_description')" />
                                <x-text-input :id="'og_description_'.$loc" class="mt-1 block w-full" type="text" :name="'translations['.$loc.'][og_description]'" :value="old('translations.'.$loc.'.og_description')" maxlength="500" />
                                <p class="mt-1 text-xs text-gray-400">{{ __('site.admin_og_description_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.og_description')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'canonical_url_'.$loc" :value="__('site.admin_canonical_url')" />
                                <x-text-input :id="'canonical_url_'.$loc" class="mt-1 block w-full" type="url" :name="'translations['.$loc.'][canonical_url]'" :value="old('translations.'.$loc.'.canonical_url')" maxlength="500" :placeholder="__('site.admin_placeholder_canonical_url')" />
                                <p class="mt-1 text-xs text-gray-400">{{ __('site.admin_canonical_url_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.canonical_url')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="'og_image_url_'.$loc" :value="__('site.admin_og_image_url')" />
                                <x-text-input :id="'og_image_url_'.$loc" class="mt-1 block w-full" type="url" :name="'translations['.$loc.'][og_image_url]'" :value="old('translations.'.$loc.'.og_image_url')" maxlength="500" :placeholder="__('site.admin_placeholder_og_image_url')" />
                                <p class="mt-1 text-xs text-gray-400">{{ __('site.admin_og_image_url_hint') }}</p>
                                <x-input-error :messages="$errors->get('translations.'.$loc.'.og_image_url')" class="mt-2" />
                            </div>
                            @include('admin.partials.seo-robots-box', [
                                'namePrefix' => 'translations['.$loc.']',
                                'noindexChecked' => old('translations.'.$loc.'.robots_noindex'),
                                'nofollowChecked' => old('translations.'.$loc.'.robots_nofollow'),
                                'errorNoindex' => $errors->get('translations.'.$loc.'.robots_noindex'),
                                'errorNofollow' => $errors->get('translations.'.$loc.'.robots_nofollow'),
                            ])
                            <x-admin.seo-preview variant="generic" />
                        </div>
                    </fieldset>
                @endforeach

                <div class="flex gap-3">
                    <x-primary-button>{{ __('site.common_save') }}</x-primary-button>
                    <x-admin.btn :href="route('admin.categories.index')">{{ __('site.common_cancel') }}</x-admin.btn>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        @include('admin.partials.seo-preview-scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                (function initCategoryLocaleTabs() {
                    const tabsWrap = document.querySelector('[data-category-locale-tabs]');
                    if (!tabsWrap) return;
                    const tabButtons = tabsWrap.querySelectorAll('[data-category-locale-tab]');
                    const panels = document.querySelectorAll('[data-category-locale-panel]');
                    const activeLocaleInput = document.getElementById('active_locale');
                    const initialLocale = (activeLocaleInput && activeLocaleInput.value) || '{{ $initialLocale }}';
                    const requiredFields = ['name', 'slug'];

                    const show = function (locale) {
                        tabButtons.forEach(function (btn) {
                            btn.classList.toggle('is-active', btn.getAttribute('data-category-locale-tab') === locale);
                        });
                        panels.forEach(function (panel) {
                            panel.classList.toggle('hidden', panel.getAttribute('data-category-locale-panel') !== locale);
                        });
                        if (activeLocaleInput) {
                            activeLocaleInput.value = locale;
                        }
                    };

                    tabButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            show(btn.getAttribute('data-category-locale-tab'));
                        });
                    });

                    const updateMissingBadges = function () {
                        tabButtons.forEach(function (btn) {
                            const locale = btn.getAttribute('data-category-locale-tab');
                            const badge = btn.querySelector('[data-category-locale-missing="' + locale + '"]');
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

                    document.querySelectorAll('[data-category-locale-panel] input, [data-category-locale-panel] textarea, [data-category-locale-panel] select')
                        .forEach(function (el) {
                            el.addEventListener('input', updateMissingBadges);
                            el.addEventListener('change', updateMissingBadges);
                        });

                    show(initialLocale);
                    updateMissingBadges();
                })();
                window.initGenericSeoPreview({ titleField: 'name' });
            });
        </script>
    @endpush
</x-app-layout>
