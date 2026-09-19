<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            (function () {
                try {
                    var key = 'novara-admin-theme';
                    var root = document.documentElement;
                    var stored = localStorage.getItem(key);
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    root.classList.toggle('dark', stored ? stored === 'dark' : prefersDark);
                } catch (e) {}
            })();
        </script>

        @php($appTitle = html_entity_decode((string) config('app.name', 'Laravel'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        <title>{{ $appTitle }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-theme h-full font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-950">
            <div class="absolute right-4 top-4">
                <button
                    type="button"
                    data-admin-theme-toggle
                    data-label-to-dark="{{ __('site.theme_to_dark') }}"
                    data-label-to-light="{{ __('site.theme_to_light') }}"
                    class="admin-btn"
                    aria-label="{{ __('site.theme_to_dark') }}"
                >
                    <span data-admin-theme-label>{{ __('site.theme_to_dark') }}</span>
                </button>
            </div>
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-gray-300" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg dark:border dark:border-gray-800 dark:bg-gray-900">
                {{ $slot }}
            </div>
        </div>
        <script>
            (function () {
                var key = 'novara-admin-theme';
                var root = document.documentElement;
                var toggles = document.querySelectorAll('[data-admin-theme-toggle]');
                if (!toggles.length) return;

                function syncToggleState() {
                    var isDark = root.classList.contains('dark');
                    toggles.forEach(function (btn) {
                        var label = isDark ? btn.dataset.labelToLight : btn.dataset.labelToDark;
                        btn.setAttribute('aria-label', label || '');
                        var textNode = btn.querySelector('[data-admin-theme-label]');
                        if (textNode && label) textNode.textContent = label;
                    });
                }

                toggles.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var nextDark = !root.classList.contains('dark');
                        root.classList.toggle('dark', nextDark);
                        try { localStorage.setItem(key, nextDark ? 'dark' : 'light'); } catch (e) {}
                        syncToggleState();
                    });
                });

                syncToggleState();
            })();
        </script>
    </body>
</html>
