<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_section_analytics_verification') }}</p>
    <div class="mt-3 space-y-3">
        <x-admin.setting-toggle
            :label="__('site.admin_settings_ga4_enabled')"
            name="ga4_enabled"
            :checked="\App\Models\Setting::get('ga4_enabled', true)"
            badge-type="db"
            :hint="__('site.admin_settings_hint_ga4_enabled')"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_ga4_measurement_id')"
            name="ga4_measurement_id"
            :value="\App\Models\Setting::get('ga4_measurement_id', '')"
            badge-type="db"
            :placeholder="__('site.admin_settings_placeholder_ga4_measurement_id')"
            :hint="__('site.admin_settings_hint_ga4_measurement_id')"
            input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_google_site_verification')"
            name="google_site_verification"
            :value="\App\Models\Setting::get('google_site_verification', '')"
            badge-type="db"
            :hint="__('site.admin_settings_hint_google_site_verification')"
            input-class="mt-1.5 w-full max-w-2xl rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-toggle
            :label="__('site.admin_settings_adsense_enabled')"
            name="adsense_enabled"
            :checked="\App\Models\Setting::get('adsense_enabled', false)"
            badge-type="db"
            :hint="__('site.admin_settings_hint_adsense_enabled')"
        />
        <x-admin.setting-input
            :label="__('site.admin_settings_adsense_client_id')"
            name="adsense_client_id"
            :value="\App\Models\Setting::get('adsense_client_id', '')"
            badge-type="db"
            :placeholder="__('site.admin_settings_placeholder_adsense_client_id')"
            :hint="__('site.admin_settings_hint_adsense_client_id')"
            input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            label="AdSense Makale-İçi Slot ID"
            name="adsense_slot_in_article"
            :value="\App\Models\Setting::get('adsense_slot_in_article', '')"
            badge-type="db"
            placeholder="1234567890"
            hint="Makale gövdesinin hemen altında gösterilecek 'In-article' reklam birimi slot ID'si."
            input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <x-admin.setting-input
            label="AdSense Görüntülü Reklam Slot ID"
            name="adsense_slot_display"
            :value="\App\Models\Setting::get('adsense_slot_display', '')"
            badge-type="db"
            placeholder="0987654321"
            hint="Makale sayfasının altında gösterilecek 'Display' reklam birimi slot ID'si."
            input-class="mt-1.5 w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
    </div>
</div>

<div class="px-6 py-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_settings_api_title') }}</p>
    <div class="mt-3 divide-y divide-gray-100 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_anthropic_api_key') }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('site.admin_settings_anthropic_env_key') }}</p>
                <x-admin.config-source-badge class="mt-1" type="env" />
            </div>
            @if(config('services.anthropic.key'))
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    {{ substr(config('services.anthropic.key'), 0, 12) }}...
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    {{ __('site.admin_settings_api_not_set') }}
                </span>
            @endif
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_unsplash_api_key') }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ __('site.admin_settings_unsplash_env_key') }}</p>
                <x-admin.config-source-badge class="mt-1" type="env" />
            </div>
            @if(config('services.unsplash.access_key'))
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    {{ substr(config('services.unsplash.access_key'), 0, 12) }}...
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    {{ __('site.admin_settings_api_not_set') }}
                </span>
            @endif
        </div>
    </div>
    <p class="mt-2 text-xs text-gray-500">{{ __('site.admin_settings_api_env_hint') }}</p>
</div>
