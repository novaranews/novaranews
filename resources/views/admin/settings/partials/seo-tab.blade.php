<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_section_global_seo_defaults') }}</p>
    <div class="mt-3 space-y-3">
        <x-admin.setting-input
            :label="__('site.admin_settings_default_meta_title')"
            name="default_meta_title"
            :value="\App\Models\Setting::get('default_meta_title', '')"
            badge-type="db"
            :hint="__('site.admin_settings_hint_default_meta_title')"
            maxlength="160"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_default_meta_description')"
            name="default_meta_description"
            :value="\App\Models\Setting::get('default_meta_description', '')"
            :hint="__('site.admin_settings_hint_default_meta_description')"
            maxlength="500"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_default_og_image_url')"
            name="default_og_image_url"
            :value="\App\Models\Setting::get('default_og_image_url', '/og-default.jpg')"
            maxlength="500"
            :placeholder="__('site.admin_settings_placeholder_default_og_image_url')"
            :hint="__('site.admin_settings_hint_default_og_image_url').' /og-default.jpg'"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Localized Homepage SEO</p>
    <p class="mt-1 text-xs text-gray-500">Per-locale fallback title and description for the homepage.</p>
    <div class="mt-3 space-y-4">
        @foreach(config('novaranews.locales', ['en']) as $loc)
            @php
                $locUpper = strtoupper($loc);
                $titleKey = 'default_meta_title_'.$loc;
                $descKey = 'default_meta_description_'.$loc;
            @endphp
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $locUpper }}</p>
                <div class="mt-3 space-y-3">
                    <x-admin.setting-input
                        :label="__('site.admin_settings_default_meta_title').' ('.$locUpper.')'"
                        :name="$titleKey"
                        :value="\App\Models\Setting::get($titleKey, __('site.home_title', [], $loc))"
                        badge-type="db"
                        :hint="__('site.admin_settings_hint_default_meta_title')"
                        maxlength="160"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <x-admin.setting-input
                        :label="__('site.admin_settings_default_meta_description').' ('.$locUpper.')'"
                        :name="$descKey"
                        :value="\App\Models\Setting::get($descKey, __('site.meta_description_default', [], $loc))"
                        badge-type="db"
                        :hint="__('site.admin_settings_hint_default_meta_description')"
                        maxlength="500"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Localized Category SEO Templates</p>
    <p class="mt-1 text-xs text-gray-500">Fallback templates for category pages. Use <code>:category</code> and <code>:site</code> placeholders.</p>
    <div class="mt-3 space-y-4">
        @foreach(config('novaranews.locales', ['en']) as $loc)
            @php
                $locUpper = strtoupper($loc);
                $metaTplKey = 'category_meta_description_template_'.$loc;
                $introTplKey = 'category_intro_template_'.$loc;
            @endphp
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $locUpper }}</p>
                <div class="mt-3 space-y-3">
                    <x-admin.setting-input
                        :label="'Category meta template ('.$locUpper.')'"
                        :name="$metaTplKey"
                        :value="\App\Models\Setting::get($metaTplKey, __('site.category_meta_description', [], $loc))"
                        badge-type="db"
                        :hint="'Used when category-specific meta description is empty.'"
                        maxlength="500"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <x-admin.setting-input
                        :label="'Category intro template ('.$locUpper.')'"
                        :name="$introTplKey"
                        :value="\App\Models\Setting::get($introTplKey, __('site.category_intro', [], $loc))"
                        badge-type="db"
                        :hint="'Used when category-specific intro is empty.'"
                        maxlength="500"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Localized Listing SEO Templates</p>
    <p class="mt-1 text-xs text-gray-500">Fallback templates for search and author pages.</p>
    <div class="mt-3 space-y-4">
        @foreach(config('novaranews.locales', ['en']) as $loc)
            @php
                $locUpper = strtoupper($loc);
                $searchKey = 'search_meta_template_'.$loc;
                $authorsKey = 'authors_meta_template_'.$loc;
                $authorKey = 'author_meta_template_'.$loc;
            @endphp
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $locUpper }}</p>
                <div class="mt-3 space-y-3">
                    <x-admin.setting-input
                        :label="'Search meta template ('.$locUpper.')'"
                        :name="$searchKey"
                        :value="\App\Models\Setting::get($searchKey, __('site.search_meta', [], $loc))"
                        badge-type="db"
                        :hint="'Use :query and :site placeholders.'"
                        maxlength="500"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <x-admin.setting-input
                        :label="'Authors list meta template ('.$locUpper.')'"
                        :name="$authorsKey"
                        :value="\App\Models\Setting::get($authorsKey, __('site.authors_meta', [], $loc))"
                        badge-type="db"
                        :hint="'Use :site placeholder.'"
                        maxlength="500"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <x-admin.setting-input
                        :label="'Author profile meta template ('.$locUpper.')'"
                        :name="$authorKey"
                        :value="\App\Models\Setting::get($authorKey, ':name - :site')"
                        badge-type="db"
                        :hint="'Use :name, :title and :site placeholders.'"
                        maxlength="500"
                        input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_section_editorial_guardrail') }}</p>
    <div class="mt-3 space-y-3">
        <x-admin.setting-toggle
            :label="__('site.admin_settings_editorial_guardrail_enabled')"
            name="editorial_guardrail_enabled"
            :checked="\App\Models\Setting::get('editorial_guardrail_enabled', true)"
            badge-type="db"
            :hint="__('site.admin_settings_hint_editorial_guardrail_enabled')"
        />
        <div class="grid gap-3 md:grid-cols-2">
            <x-admin.setting-input
                :label="__('site.admin_settings_editorial_guardrail_medium_threshold')"
                name="editorial_guardrail_medium_threshold"
                type="number"
                :value="\App\Models\Setting::get('editorial_guardrail_medium_threshold', 35)"
                min="0"
                max="100"
                :hint="__('site.admin_settings_hint_editorial_guardrail_medium_threshold')"
                input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <x-admin.setting-input
                :label="__('site.admin_settings_editorial_guardrail_high_threshold')"
                name="editorial_guardrail_high_threshold"
                type="number"
                :value="\App\Models\Setting::get('editorial_guardrail_high_threshold', 60)"
                min="0"
                max="100"
                :hint="__('site.admin_settings_hint_editorial_guardrail_high_threshold')"
                input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
        </div>
        <div>
            <label class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_editorial_guardrail_generic_phrases') }}</label>
            <x-admin.config-source-badge class="ml-2" type="db" />
            <textarea
                name="editorial_guardrail_generic_phrases"
                rows="5"
                class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="{{ __('site.admin_settings_placeholder_editorial_guardrail_phrases') }}"
            >{{ \App\Models\Setting::get('editorial_guardrail_generic_phrases', implode("\n", config('editorial_guardrail.generic_phrases', []))) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">{{ __('site.admin_settings_hint_editorial_guardrail_generic_phrases') }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_editorial_guardrail_cta_phrases') }}</label>
            <textarea
                name="editorial_guardrail_cta_phrases"
                rows="4"
                class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="{{ __('site.admin_settings_placeholder_editorial_guardrail_phrases') }}"
            >{{ \App\Models\Setting::get('editorial_guardrail_cta_phrases', implode("\n", config('editorial_guardrail.cta_phrases', []))) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">{{ __('site.admin_settings_hint_editorial_guardrail_cta_phrases') }}</p>
        </div>
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_section_publisher_branding') }}</p>
    <div class="mt-3 space-y-3">
        <x-admin.setting-input
            :label="__('site.admin_settings_publisher_name')"
            name="publisher_name"
            :value="\App\Models\Setting::get('publisher_name', config('app.name'))"
            badge-type="db"
            :hint="__('site.admin_settings_hint_publisher_name')"
            maxlength="160"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_publisher_logo_url')"
            name="publisher_logo_url"
            :value="\App\Models\Setting::get('publisher_logo_url', '')"
            maxlength="500"
            :placeholder="__('site.admin_settings_placeholder_publisher_logo_url')"
            :hint="__('site.admin_settings_hint_publisher_logo_url').' /logo.png'"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_site_logo_url')"
            name="site_logo_url"
            :value="\App\Models\Setting::get('site_logo_url', '/logo.png')"
            maxlength="500"
            :placeholder="__('site.admin_settings_placeholder_site_logo_url')"
            :hint="__('site.admin_settings_hint_site_logo_url').' /logo.png'"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_site_favicon_url')"
            name="site_favicon_url"
            :value="\App\Models\Setting::get('site_favicon_url', '/favicon.svg')"
            maxlength="500"
            :placeholder="__('site.admin_settings_placeholder_site_favicon_url')"
            :hint="__('site.admin_settings_hint_site_favicon_url').' /favicon.svg'"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />

        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Public Brand Assets</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">You can use absolute URLs or root-relative paths (for example <code>/favicon.svg</code>).</p>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <x-admin.setting-input
                    label="Header/Footer Mark URL"
                    name="site_mark_logo_url"
                    :value="\App\Models\Setting::get('site_mark_logo_url', '/images/novaranews-mark.svg')"
                    maxlength="500"
                    hint="Used in header and footer brand mark image."
                />
                <x-admin.setting-input
                    label="Favicon SVG URL"
                    name="site_favicon_svg_url"
                    :value="\App\Models\Setting::get('site_favicon_svg_url', '/favicon.svg')"
                    maxlength="500"
                    hint="Primary favicon (SVG)."
                />
                <x-admin.setting-input
                    label="Favicon ICO URL"
                    name="site_favicon_ico_url"
                    :value="\App\Models\Setting::get('site_favicon_ico_url', '/favicon.ico')"
                    maxlength="500"
                    hint="Legacy browser favicon (ICO)."
                />
                <x-admin.setting-input
                    label="Apple Touch Icon URL"
                    name="site_apple_touch_icon_url"
                    :value="\App\Models\Setting::get('site_apple_touch_icon_url', '/images/apple-touch-icon.svg')"
                    maxlength="500"
                    hint="General iOS home-screen icon."
                />
                <x-admin.setting-input
                    label="Apple Touch Icon PNG URL"
                    name="site_apple_touch_icon_png_url"
                    :value="\App\Models\Setting::get('site_apple_touch_icon_png_url', '/apple-touch-icon.png')"
                    maxlength="500"
                    hint="180x180 PNG icon for Apple devices."
                />
            </div>
        </div>
    </div>
</div>
