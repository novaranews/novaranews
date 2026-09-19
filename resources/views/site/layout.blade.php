<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            try {
                var k = 'novara-theme', r = document.documentElement, s = localStorage.getItem(k),
                    d = window.matchMedia('(prefers-color-scheme: dark)').matches;
                r.classList.toggle('dark', s === 'dark' || (s !== 'light' && d));
            } catch (e) {}
        })();
    </script>
    @php
        $siteName = \App\Support\HtmlText::decodeEntitiesForBlade((string) \App\Models\Setting::site('site_name', config('app.name')));
        $siteTagline = \App\Support\HtmlText::decodeEntitiesForBlade((string) \App\Models\Setting::site('site_tagline', __('site.footer_tagline')));
        $locale = app()->getLocale();
        $defaultMetaTitle = \App\Support\HtmlText::decodeEntitiesForBlade(
            \App\Support\SeoTemplate::localeValue(
                'default_meta_title',
                $locale,
                (string) \App\Models\Setting::site('default_meta_title', $siteName)
            )
        );
        $faviconSvgUrl = \App\Models\Setting::site('site_favicon_svg_url', \App\Models\Setting::site('site_favicon_url', asset('favicon.svg')));
        $faviconIcoUrl = \App\Models\Setting::site('site_favicon_ico_url', asset('favicon.ico'));
        $appleTouchIconUrl = \App\Models\Setting::site('site_apple_touch_icon_url', asset('images/apple-touch-icon.svg'));
        $appleTouchIconPngUrl = \App\Models\Setting::site('site_apple_touch_icon_png_url', asset('apple-touch-icon.png'));
        $siteMarkLogoUrl = \App\Models\Setting::site('site_mark_logo_url', asset('images/novaranews-mark.svg'));
        $socialCount = count(\App\Models\Setting::publicSocialFooterLinks());
        // DB/admin may store "&amp;"; Blade {{ }} would emit "&amp;amp;" without normalizing first.
        $metaTitle = \App\Support\HtmlText::decodeEntitiesForBlade((string) $__env->yieldContent('title', $defaultMetaTitle));
    @endphp
    <title>{!! htmlspecialchars($metaTitle, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</title>
    <link rel="icon" href="{{ $faviconSvgUrl }}" type="image/svg+xml" sizes="any">
    <link rel="alternate icon" href="{{ $faviconIcoUrl }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ $appleTouchIconUrl }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouchIconPngUrl }}">
    @hasSection('canonical')
        {{-- e() ile & -> &amp; (HTML özelliği için geçerli kaçış); ham @yield kaçışsız bırakıyordu --}}
        <link rel="canonical" href="{{ $__env->yieldContent('canonical') }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    <link rel="alternate" type="application/rss+xml" title="{{ $siteName }}" href="{{ route('feed', ['locale' => app()->getLocale()]) }}">
    @stack('hreflang')
    @php
        $ogLocaleMap = ['en' => 'en_US', 'tr' => 'tr_TR', 'de' => 'de_DE', 'fr' => 'fr_FR', 'es' => 'es_ES'];
        $currentOgLocale = $ogLocaleMap[app()->getLocale()] ?? strtolower(str_replace('-', '_', app()->getLocale()));
    @endphp
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $currentOgLocale }}">
    @foreach(config('novaranews.locales', []) as $_ogAltLoc)
        @if($_ogAltLoc !== app()->getLocale())
            <meta property="og:locale:alternate" content="{{ $ogLocaleMap[$_ogAltLoc] ?? $_ogAltLoc }}">
        @endif
    @endforeach
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('site.partials.head-verifications')
    @include('site.partials.ga4')
</head>
<body class="tech-site-bg min-h-full text-stone-900 antialiased font-sans transition-colors duration-200 dark:text-stone-100">
    {{-- ─── İçeriğe geç (erişilebilirlik) ─────────────────────────────────── --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[200] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-novara-800 focus:shadow-lg focus:ring-2 focus:ring-novara-800 dark:focus:bg-stone-900 dark:focus:text-sky-400 dark:focus:ring-sky-400">
        {{ __('site.skip_to_content') }}
    </a>

    {{-- ─── Okuma ilerleme çubuğu (genişlik app.js) ──────────────────────── --}}
    <div
        id="nv-reading-progress"
        class="fixed left-0 top-0 z-[100] h-0.5 bg-novara-800 transition-[width] duration-75 dark:bg-sky-400"
        style="width:0%"
        role="progressbar"
        aria-label="{{ __('site.reading_progress') }}"
        aria-valuenow="0"
        aria-valuemin="0"
        aria-valuemax="100"
    ></div>

    <div class="nv-site-header-wrap">
        {{-- HEADER - sticky --}}
        <header class="sticky top-0 z-40 border-b border-stone-200 bg-white/95 backdrop-blur-sm dark:border-stone-800 dark:bg-stone-900/95">
            <div class="nv-container flex items-center justify-between gap-4 py-3.5">
                <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="flex min-w-0 items-center gap-3.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-novara-800/50 focus-visible:ring-offset-2 dark:focus-visible:ring-sky-400/50">
                    <img src="{{ $siteMarkLogoUrl }}" width="36" height="36" class="h-9 w-9 shrink-0" alt="" aria-hidden="true">
                    <span class="font-serif text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 sm:text-2xl">
                        {!! htmlspecialchars($siteName, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                    </span>
                </a>

                <nav class="hidden flex-1 flex-wrap items-center justify-center gap-x-1 gap-y-1 text-sm font-semibold text-stone-600 dark:text-stone-400 md:flex" aria-label="{{ __('site.nav_primary') }}">
                    @foreach($navCategories ?? [] as $cat)
                        @php $tr = $cat->translate(); @endphp
                        @if($tr)
                            <a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $tr->slug]) }}" class="rounded-md px-2.5 py-1.5 transition-colors hover:bg-stone-100 hover:text-novara-800 dark:hover:bg-stone-800 dark:hover:text-sky-400">{{ $tr->name }}</a>
                        @endif
                    @endforeach
                </nav>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
                    <x-site.btn :href="route('search', ['locale' => app()->getLocale()])" variant="icon" aria-label="{{ __('site.search_heading') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span class="sr-only">{{ __('site.search_heading') }}</span>
                    </x-site.btn>

                    <x-site.btn
                        type="button"
                        id="theme-toggle"
                        variant="icon"
                        data-label-to-dark="{{ __('site.theme_to_dark') }}"
                        data-label-to-light="{{ __('site.theme_to_light') }}"
                        aria-pressed="false"
                        aria-label="{{ __('site.theme_to_dark') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 dark:hidden" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-5 w-5 dark:inline" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </x-site.btn>

                    {{-- Yer imleri: <details>; liste nv-favorites-popover.js + localStorage --}}
                    <details class="nv-fav-details relative shrink-0">
                        <summary
                            class="nv-icon-btn relative list-none cursor-pointer hover:text-novara-800 dark:hover:text-sky-400 [&::-webkit-details-marker]:hidden"
                            aria-label="{{ __('site.favorites_open') }}"
                            aria-controls="nv-favorites-panel"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0z" />
                            </svg>
                            <span id="nv-fav-badge" class="absolute -right-0.5 -top-0.5 hidden min-h-4 min-w-4 items-center justify-center rounded-full bg-novara-800 px-0.5 text-[10px] font-bold text-white dark:bg-sky-500" aria-hidden="true"></span>
                        </summary>
                        <div
                            id="nv-favorites-panel"
                            class="max-sm:fixed max-sm:top-[65px] max-sm:left-4 max-sm:right-4 max-sm:w-auto sm:absolute sm:right-0 sm:top-full sm:w-[min(22rem,calc(100vw-2rem))] z-50 mt-2 rounded-xl border border-stone-200 bg-white py-2 shadow-lg dark:border-stone-600 dark:bg-stone-900"
                            role="region"
                            aria-label="{{ __('site.favorites') }}"
                        >
                            <div class="flex items-center justify-between border-b border-stone-100 px-3 pb-2 dark:border-stone-700">
                                <span class="text-sm font-bold text-stone-900 dark:text-stone-100">{{ __('site.favorites') }}</span>
                                <button
                                    type="button"
                                    class="nv-icon-btn-sm shrink-0 text-stone-400 hover:text-stone-600 dark:hover:text-stone-300"
                                    onclick="this.closest('details').removeAttribute('open')"
                                    aria-label="{{ __('site.favorites_close') }}"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <p id="nv-fav-empty" class="px-3 py-6 text-center text-sm text-stone-500 dark:text-stone-400">{{ __('site.favorites_empty') }}</p>
                            <ul id="nv-fav-list" class="hidden max-h-[min(70vh,22rem)] overflow-y-auto py-1" data-remove-label="{{ __('site.favorites_remove') }}"></ul>
                        </div>
                    </details>

                    <label for="site-locale-switcher" class="sr-only">{{ __('site.language') }}</label>
                    <select
                        id="site-locale-switcher"
                        class="max-w-[5.25rem] rounded-full border border-stone-200 bg-white py-1.5 pl-2 pr-7 text-xs font-semibold text-stone-700 transition hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:bg-stone-800 sm:max-w-none sm:pl-2.5 sm:pr-8 sm:text-sm"
                        onchange="window.location.href=this.value"
                        aria-label="{{ __('site.language') }}"
                    >
                        @foreach(config('novaranews.locales') as $loc)
                            <option value="{{ ($localeSwitchUrls ?? [])[$loc] ?? route('home', ['locale' => $loc]) }}" @selected(app()->getLocale() === $loc)>
                                {{ strtoupper($loc) }}
                            </option>
                        @endforeach
                    </select>

                    <x-site.btn
                        type="button"
                        variant="icon"
                        class="md:hidden"
                        id="nv-mobile-nav-open"
                        aria-expanded="false"
                        aria-controls="mobile-nav-panel"
                        aria-label="{{ __('site.menu_open') }}"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </x-site.btn>
                </div>
            </div>
        </header>

        {{-- MOBILE NAV --}}
        <div
            id="mobile-nav-panel"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-label="{{ __('site.menu') }}"
            class="fixed inset-0 z-50 hidden md:hidden"
        >
            <div class="absolute inset-0 bg-stone-900/50 backdrop-blur-[2px]" data-nv-mobile-nav-backdrop></div>
            <div
                class="absolute right-0 top-0 flex h-full w-[min(100vw-2.5rem,20rem)] flex-col bg-white shadow-xl dark:bg-stone-900"
            >
                <div class="flex items-center justify-between border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                    <span class="text-sm font-bold text-stone-900 dark:text-stone-100">{{ __('site.menu') }}</span>
                    <x-site.btn type="button" variant="icon-lg" id="nv-mobile-nav-close" aria-label="{{ __('site.menu_close') }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </x-site.btn>
                </div>
                <nav
                    class="flex-1 overflow-y-auto px-4 py-4 text-base font-semibold text-stone-800 dark:text-stone-200"
                    aria-label="{{ __('site.nav_primary') }}"
                >
                    <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100 dark:hover:bg-stone-800">{{ __('site.home_breadcrumb') }}</a>
                    @foreach($navCategories ?? [] as $cat)
                        @php $tr = $cat->translate(); @endphp
                        @if($tr)
                            <a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $tr->slug]) }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100 dark:hover:bg-stone-800">{{ $tr->name }}</a>
                        @endif
                    @endforeach
                </nav>
                @auth
                    @if(auth()->user()->is_admin)
                        <div class="border-t border-stone-200 p-4 dark:border-stone-700">
                            <a href="{{ route('admin.articles.index') }}" class="font-semibold text-novara-800 dark:text-sky-400">{{ __('site.admin') }}</a>
                        </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    {{-- BREAKING NEWS - son 10 haber, tek satirda otomatik donusumlu --}}
    @php
        $breakingTickerItems = collect($breakingNewsItems ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['url']) && ! empty($item['title']))
            ->map(fn ($item) => ['url' => $item['url'], 'title' => $item['title']])
            ->values()
            ->all();
    @endphp
    @if(count($breakingTickerItems) > 0)
        <div
            id="nv-breaking-ticker"
            class="bg-red-700 text-white"
            role="region"
            aria-label="{{ __('site.breaking') }}"
        >
            <div class="nv-container flex items-center gap-0 px-2 py-2 text-sm sm:pl-4">
                <span class="inline-flex shrink-0 items-center gap-1.5 border-r border-white/25 px-2 pr-3 font-bold uppercase tracking-wider sm:pl-0">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    {{ __('site.breaking') }}
                </span>
                <div class="relative min-h-[1.35rem] min-w-0 flex-1 px-2 sm:px-3" aria-live="polite" aria-atomic="true">
                    @foreach($breakingTickerItems as $idx => $item)
                        <a
                            href="{{ $item['url'] }}"
                            data-nv-breaking-item
                            @class([
                                'nv-breaking-item absolute inset-x-2 top-0 block truncate font-medium text-white/95 underline decoration-white/40 underline-offset-2 hover:text-white hover:decoration-white sm:inset-x-3',
                                'hidden' => $idx > 0,
                            ])
                        >{{ $item['title'] }}</a>
                    @endforeach
                </div>
                @if(count($breakingTickerItems) > 1)
                <div class="flex shrink-0 items-center gap-0.5 border-l border-white/25 pl-1 sm:pl-2">
                    <x-site.btn
                        type="button"
                        variant="ticker"
                        data-nv-breaking-prev
                        aria-label="{{ __('site.breaking_prev') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </x-site.btn>
                    <x-site.btn
                        type="button"
                        variant="ticker"
                        data-nv-breaking-next
                        aria-label="{{ __('site.breaking_next') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </x-site.btn>
                </div>
                @endif
            </div>
        </div>
    @endif

    <main id="main-content">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @php
        $footerWhatsappRaw = trim((string) \App\Models\Setting::site('footer_whatsapp', ''));
        $footerAddress = trim((string) \App\Models\Setting::site('footer_address', ''));
        $sourceCodeUrl = trim((string) config('novaranews.source_code_url', ''));
        $footerWaDigits = $footerWhatsappRaw !== '' ? preg_replace('/\D+/', '', $footerWhatsappRaw) : '';
        $footerWaUrl = (is_string($footerWaDigits) && strlen($footerWaDigits) >= 8)
            ? 'https://wa.me/'.$footerWaDigits
            : null;
    @endphp
    <footer class="mt-16 border-t border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="nv-container py-12">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="inline-flex items-center gap-2.5">
                        <img src="{{ $siteMarkLogoUrl }}" width="40" height="40" class="h-10 w-10 shrink-0" alt="" aria-hidden="true">
                        <span class="font-serif text-xl font-bold text-stone-900 dark:text-stone-100">{!! htmlspecialchars($siteName, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
                    </a>
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-stone-600 dark:text-stone-400">{!! htmlspecialchars($siteTagline, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-500">{{ __('site.footer_section_pages') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm font-medium text-stone-700 dark:text-stone-300">
                        <li><a href="{{ page_url('about') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.about') }}</a></li>
                        <li><a href="{{ route('authors.index', ['locale' => app()->getLocale()]) }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.authors_title') }}</a></li>
                        <li><a href="{{ page_url('contact') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.contact') }}</a></li>
                        <li><a href="{{ page_url('editorial-policy') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.editorial_policy') }}</a></li>
                        <li><a href="{{ page_url('privacy') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.privacy_policy') }}</a></li>
                        <li><a href="{{ page_url('cookies') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.cookies') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-500">{{ __('site.footer_section_feeds') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm font-medium text-stone-700 dark:text-stone-300">
                        <li><a href="{{ route('feed', ['locale' => app()->getLocale()]) }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.footer_rss_locale') }}</a></li>
                        <li><a href="{{ url('/sitemap.xml') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.footer_sitemap') }}</a></li>
                        <li><a href="{{ url('/news-sitemap.xml') }}" class="transition hover:text-novara-800 dark:hover:text-sky-400">{{ __('site.footer_google_news_sitemap') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-500">{{ __('site.footer_section_follow') }}</h2>
                    <div class="mt-4">
                        @include('site.partials.social-links')
                    </div>
                    @if($footerWaUrl || $footerAddress !== '')
                        <div class="mt-6 flex flex-col gap-3 border-t border-stone-200 pt-6 text-sm text-stone-600 dark:border-stone-700 dark:text-stone-400">
                            @if($footerWaUrl)
                                <a href="{{ $footerWaUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 font-medium text-novara-800 transition hover:underline dark:text-sky-400">
                                    @include('site.partials.icons.whatsapp')
                                    <span>{!! htmlspecialchars($footerWhatsappRaw, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
                                </a>
                            @endif
                            @if($footerAddress !== '')
                                <p class="max-w-xs whitespace-pre-line leading-relaxed">{!! htmlspecialchars($footerAddress, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-10 border-t border-stone-200 pt-8 dark:border-stone-800">
                <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        &copy; {{ date('Y') }} {!! htmlspecialchars($siteName, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}. {{ __('site.footer_rights') }}
                    </p>
                    <div class="flex items-center gap-4 text-xs text-stone-400">
                        @if($sourceCodeUrl !== '')
                            <a href="{{ $sourceCodeUrl }}" target="_blank" rel="noopener noreferrer" class="transition hover:text-novara-800 dark:hover:text-sky-400">
                                {{ __('site.footer_source_code') }}
                            </a>
                        @endif
                        <a href="{{ route('feed', ['locale' => app()->getLocale()]) }}" class="transition hover:text-novara-800 dark:hover:text-sky-400" title="RSS">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27V4.44m0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93V10.1Z"/></svg>
                            <span class="sr-only">RSS</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- Yukari cik - asagi kaydirildiginda sabit dugme --}}
    <script src="{{ asset('js/nv-favorites-popover.js') }}" defer></script>
    <script src="{{ asset('js/nv-webmcp.js') }}"></script>

    <x-site.btn
        id="nv-scroll-top-fab"
        type="button"
        variant="fab"
        class="fixed bottom-6 right-4 z-50 hidden sm:right-6"
        aria-hidden="true"
        aria-label="{{ __('site.scroll_to_top') }}"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
        </svg>
    </x-site.btn>

    @include('site.partials.cookie-banner')
    @stack('scripts')
</body>
</html>
