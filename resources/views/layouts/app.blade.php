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
    <body class="admin-theme h-full bg-gray-100 font-sans antialiased text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-950">
            @include('layouts.navigation')

            <div class="md:pl-64">
                <!-- Page Heading -->
                @isset($header)
                    <header class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Global admin flash/messages -->
                @if (session('status') || session('success') || session('error') || $errors->any())
                    <div class="px-4 pt-4 sm:px-6 lg:px-8">
                        @if (session('success') || session('status'))
                            <div class="mb-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/70 dark:bg-emerald-900/20 dark:text-emerald-300">
                                {{ session('success') ?? session('status') }}
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="mb-3 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-800/70 dark:bg-rose-900/20 dark:text-rose-300">
                                {{ session('error') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800/70 dark:bg-amber-900/20 dark:text-amber-300">
                                <p class="font-semibold">Please review the form errors below.</p>
                                <ul class="mt-1 list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
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
