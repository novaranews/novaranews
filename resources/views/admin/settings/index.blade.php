<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_settings_title') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">

            @if (session('success'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif

            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <span class="font-semibold text-gray-700 dark:text-gray-100">{{ __('site.admin_settings_config_source') }}</span>
                <x-admin.config-source-badge class="ml-2" type="db" />
                <x-admin.config-source-badge class="ml-2" type="env" />
            </div>

            {{-- BOT SETTINGS --}}
            <div class="admin-card">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('site.admin_settings_bot_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('site.admin_settings_bot_desc') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="group" value="bot">

                    <div class="divide-y divide-gray-100 dark:divide-gray-800">

                        <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_bot_enabled') }}</label>
                                <p class="text-xs text-gray-500 mt-0.5">{{ __('site.admin_settings_bot_enabled_desc') }}</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="hidden" name="bot_enabled" value="0">
                                <input type="checkbox" name="bot_enabled" value="1" class="sr-only peer"
                                    {{ \App\Models\Setting::get('bot_enabled', true) ? 'checked' : '' }}>
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-gray-700 dark:peer-checked:bg-indigo-500"></div>
                            </label>
                        </div>

                        <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_mock_mode') }}</label>
                                <p class="text-xs text-gray-500 mt-0.5">{{ __('site.admin_settings_mock_desc') }}</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="hidden" name="bot_mock_mode" value="0">
                                <input type="checkbox" name="bot_mock_mode" value="1" class="sr-only peer"
                                    {{ \App\Models\Setting::get('bot_mock_mode', false) ? 'checked' : '' }}>
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-yellow-500 peer-checked:after:translate-x-full dark:bg-gray-700 dark:peer-checked:bg-yellow-500"></div>
                            </label>
                        </div>

                        <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">{{ __('site.admin_settings_auto_publish') }}</label>
                                <p class="text-xs text-gray-500 mt-0.5">{{ __('site.admin_settings_auto_publish_desc') }}</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="hidden" name="bot_auto_publish" value="0">
                                <input type="checkbox" name="bot_auto_publish" value="1" class="sr-only peer"
                                    {{ \App\Models\Setting::get('bot_auto_publish', false) ? 'checked' : '' }}>
                                <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-500 peer-checked:after:translate-x-full dark:bg-gray-700 dark:peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>

                        <div class="px-6 py-4">
                            <label class="block text-sm font-medium text-gray-900">{{ __('site.admin_settings_model') }}</label>
                            <p class="text-xs text-gray-500 mt-0.5 mb-2">{{ __('site.admin_settings_model_desc') }}</p>
                            <select name="bot_model" class="w-full max-w-xs rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @php $currentModel = \App\Models\Setting::get('bot_model', 'claude-sonnet-4-6') @endphp
                                <option value="claude-haiku-4-5-20251001" @selected($currentModel === 'claude-haiku-4-5-20251001')>{{ __('site.admin_settings_model_haiku') }}</option>
                                <option value="claude-sonnet-4-6" @selected($currentModel === 'claude-sonnet-4-6')>{{ __('site.admin_settings_model_sonnet') }}</option>
                                <option value="claude-opus-4-6" @selected($currentModel === 'claude-opus-4-6')>{{ __('site.admin_settings_model_opus') }}</option>
                            </select>
                        </div>

                        <div class="px-6 py-4">
                            <label class="block text-sm font-medium text-gray-900">{{ __('site.admin_settings_max_tokens') }}</label>
                            <p class="text-xs text-gray-500 mt-0.5 mb-2">{{ __('site.admin_settings_max_tokens_desc') }}</p>
                            <input type="number" name="bot_max_tokens"
                                value="{{ \App\Models\Setting::get('bot_max_tokens', 6144) }}"
                                min="2048" max="8192" step="256"
                                class="w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="px-6 py-4">
                            <label class="block text-sm font-medium text-gray-900">{{ __('site.admin_settings_articles_per_run') }}</label>
                            <p class="text-xs text-gray-500 mt-0.5 mb-2">{{ __('site.admin_settings_articles_per_run_desc') }}</p>
                            <input type="number" name="bot_articles_per_run"
                                value="{{ \App\Models\Setting::get('bot_articles_per_run', 20) }}"
                                min="1" max="100"
                                class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                    </div>

                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                        <x-admin.btn type="submit" variant="info" class="focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('site.admin_settings_save_bot') }}
                        </x-admin.btn>
                    </div>
                </form>
            </div>

            {{-- SITE / SEO / ANALYTICS SETTINGS --}}
            <div class="admin-card" data-nv-admin-tabs>
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('site.admin_settings_site_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('site.admin_settings_site_desc') }}</p>
                </div>

                <div class="border-b border-gray-200 bg-white px-6 pt-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-wrap gap-2" role="tablist">
                        <button type="button" role="tab" data-nv-admin-tab="site" aria-selected="true" class="rounded-md px-3 py-1.5 text-sm font-medium transition">{{ __('site.admin_settings_tab_site') }}</button>
                        <button type="button" role="tab" data-nv-admin-tab="seo" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium transition">{{ __('site.admin_settings_tab_seo') }}</button>
                        <button type="button" role="tab" data-nv-admin-tab="analytics" aria-selected="false" class="rounded-md px-3 py-1.5 text-sm font-medium transition">{{ __('site.admin_settings_tab_analytics') }}</button>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="group" value="site">

                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div data-nv-admin-tab-panel="site" role="tabpanel">
                            @include('admin.settings.partials.site-tab')
                        </div>

                        <div data-nv-admin-tab-panel="seo" role="tabpanel" hidden>
                            @include('admin.settings.partials.seo-tab')
                        </div>

                        <div data-nv-admin-tab-panel="analytics" role="tabpanel" hidden>
                            @include('admin.settings.partials.analytics-tab')
                        </div>
                    </div>

                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                        <x-admin.btn type="submit" variant="info" class="focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('site.admin_settings_save_site') }}
                        </x-admin.btn>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
