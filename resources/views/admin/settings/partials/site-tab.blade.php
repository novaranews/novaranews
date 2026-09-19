<div class="px-6 py-4">
    <x-admin.setting-input
        :label="__('site.admin_settings_site_name')"
        name="site_name"
        :value="\App\Models\Setting::get('site_name', 'Novara News')"
        badge-type="db"
        :hint="__('site.admin_settings_hint_site_name')"
        maxlength="100"
        input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    />
</div>

<div class="px-6 py-4">
    <x-admin.setting-input
        :label="__('site.admin_settings_site_tagline')"
        name="site_tagline"
        :value="\App\Models\Setting::get('site_tagline', 'Global News')"
        badge-type="db"
        :hint="__('site.admin_settings_hint_site_tagline')"
        maxlength="160"
        input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    />
</div>

<div class="px-6 py-4">
    <x-admin.setting-input
        :label="__('site.admin_settings_articles_per_page')"
        name="articles_per_page"
        type="number"
        :value="\App\Models\Setting::get('articles_per_page', 12)"
        :hint="__('site.admin_settings_hint_articles_per_page')"
        min="6"
        max="48"
        input-class="mt-1.5 w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    />
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_section_runtime_contact') }}</p>
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <div>
            <x-admin.setting-input
                :label="__('site.admin_settings_contact_mail_to')"
                name="contact_mail_to"
                type="email"
                :value="\App\Models\Setting::get('contact_mail_to', '')"
                badge-type="db"
                :placeholder="__('site.admin_settings_placeholder_contact_mail_to')"
                :hint="__('site.admin_settings_hint_contact_mail_to')"
            />
        </div>
        <div>
            <x-admin.setting-input
                :label="__('site.admin_settings_cache_home')"
                name="site_cache_ttl_home"
                type="number"
                :value="\App\Models\Setting::get('site_cache_ttl_home', 120)"
                min="30"
                max="86400"
                :hint="__('site.admin_settings_hint_cache_home')"
            />
        </div>
        <div>
            <x-admin.setting-input
                :label="__('site.admin_settings_cache_nav')"
                name="site_cache_ttl_nav"
                type="number"
                :value="\App\Models\Setting::get('site_cache_ttl_nav', 1800)"
                min="60"
                max="172800"
                :hint="__('site.admin_settings_hint_cache_nav')"
            />
        </div>
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_section_social_profiles') }}</p>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_settings_social_profiles_visibility_note') }}</p>
    <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
            <x-admin.setting-input badge-type="db" :label="__('site.admin_settings_social_x_url')" name="social_x_url" type="url" :value="\App\Models\Setting::get('social_x_url', '')" :hint="__('site.admin_settings_hint_social_x')" input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" />
            <x-admin.setting-toggle :label="__('site.admin_settings_social_show_on_site')" name="social_x_visible" :checked="\App\Models\Setting::get('social_x_visible', true)" badge-type="db" :hint="__('site.admin_settings_hint_social_visible')" />
        </div>
        <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
            <x-admin.setting-input badge-type="db" :label="__('site.admin_settings_social_facebook_url')" name="social_facebook_url" type="url" :value="\App\Models\Setting::get('social_facebook_url', '')" :hint="__('site.admin_settings_hint_social_facebook')" input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" />
            <x-admin.setting-toggle :label="__('site.admin_settings_social_show_on_site')" name="social_facebook_visible" :checked="\App\Models\Setting::get('social_facebook_visible', true)" badge-type="db" :hint="__('site.admin_settings_hint_social_visible')" />
        </div>
        <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
            <x-admin.setting-input badge-type="db" :label="__('site.admin_settings_social_instagram_url')" name="social_instagram_url" type="url" :value="\App\Models\Setting::get('social_instagram_url', '')" :hint="__('site.admin_settings_hint_social_instagram')" input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" />
            <x-admin.setting-toggle :label="__('site.admin_settings_social_show_on_site')" name="social_instagram_visible" :checked="\App\Models\Setting::get('social_instagram_visible', true)" badge-type="db" :hint="__('site.admin_settings_hint_social_visible')" />
        </div>
        <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
            <x-admin.setting-input badge-type="db" :label="__('site.admin_settings_social_youtube_url')" name="social_youtube_url" type="url" :value="\App\Models\Setting::get('social_youtube_url', '')" :hint="__('site.admin_settings_hint_social_youtube')" input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" />
            <x-admin.setting-toggle :label="__('site.admin_settings_social_show_on_site')" name="social_youtube_visible" :checked="\App\Models\Setting::get('social_youtube_visible', true)" badge-type="db" :hint="__('site.admin_settings_hint_social_visible')" />
        </div>
        <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
            <x-admin.setting-input badge-type="db" :label="__('site.admin_settings_social_linkedin_url')" name="social_linkedin_url" type="url" :value="\App\Models\Setting::get('social_linkedin_url', '')" :hint="__('site.admin_settings_hint_social_linkedin')" input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" />
            <x-admin.setting-toggle :label="__('site.admin_settings_social_show_on_site')" name="social_linkedin_visible" :checked="\App\Models\Setting::get('social_linkedin_visible', true)" badge-type="db" :hint="__('site.admin_settings_hint_social_visible')" />
        </div>
        <x-admin.setting-input
            :label="__('site.admin_settings_footer_whatsapp')"
            name="footer_whatsapp"
            :value="\App\Models\Setting::get('footer_whatsapp', '')"
            badge-type="db"
            :placeholder="__('site.admin_settings_placeholder_footer_whatsapp')"
            :hint="__('site.admin_settings_hint_footer_whatsapp')"
            maxlength="40"
            input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        />
        <div class="sm:col-span-2">
            <x-admin.setting-input
                type="textarea"
                rows="4"
                :label="__('site.admin_settings_footer_address')"
                name="footer_address"
                :value="\App\Models\Setting::get('footer_address', '')"
                badge-type="db"
                :hint="__('site.admin_settings_hint_footer_address')"
                maxlength="500"
                input-class="mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
            />
        </div>
    </div>
</div>
