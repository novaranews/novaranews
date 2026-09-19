@php
    $adminLocales = config('novaranews.locales', ['en']);
    $adminLocale = app()->getLocale();
    $unreadMsgCount = Auth::check() && Auth::user()->is_admin
        ? \App\Models\ContactMessage::whereNull('read_at')->count()
        : 0;
    $pendingCommentCount = Auth::check() && Auth::user()->is_admin
        ? \App\Models\Comment::where('approved', false)->count()
        : 0;
    $isAdmin = Auth::check() && Auth::user()->is_admin;
    $navItems = [
        ['route' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'label' => __('site.admin_nav_dashboard')],
        ['route' => route('admin.articles.index'), 'active' => request()->routeIs('admin.articles.*'), 'label' => __('site.admin_nav_articles')],
        ['route' => route('admin.categories.index'), 'active' => request()->routeIs('admin.categories.*'), 'label' => __('site.admin_nav_categories')],
        ['route' => route('admin.static-pages.index'), 'active' => request()->routeIs('admin.static-pages.*'), 'label' => __('site.admin_nav_static_pages')],
        ['route' => route('admin.ai-generations.index'), 'active' => request()->routeIs('admin.ai-generations.*'), 'label' => __('site.admin_nav_ai_bot')],
        ['route' => route('admin.media.index'), 'active' => request()->routeIs('admin.media.*'), 'label' => __('site.admin_nav_media')],
        ['route' => route('admin.redirects.index'), 'active' => request()->routeIs('admin.redirects.*'), 'label' => 'Redirects'],
        ['route' => route('admin.governance.revisions'), 'active' => request()->routeIs('admin.governance.*'), 'label' => 'Governance'],
        ['route' => route('admin.comments.index'), 'active' => request()->routeIs('admin.comments.*'), 'label' => __('site.admin_nav_comments').($pendingCommentCount > 0 ? ' ('.$pendingCommentCount.')' : '')],
        ['route' => route('admin.contact-messages.index'), 'active' => request()->routeIs('admin.contact-messages.*'), 'label' => __('site.admin_nav_contact').($unreadMsgCount > 0 ? ' ('.$unreadMsgCount.')' : '')],
        ['route' => route('admin.settings.index'), 'active' => request()->routeIs('admin.settings.*'), 'label' => __('site.admin_nav_settings')],
    ];
@endphp
<div class="border-b border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:hidden">
    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
            <x-application-logo class="h-8 w-auto fill-current text-gray-800 dark:text-gray-100" />
            <span class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ config('app.name') }}</span>
        </a>
        <button
            type="button"
            id="nv-admin-sidebar-open"
            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            aria-expanded="false"
            aria-controls="nv-admin-sidebar-mobile"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</div>

<aside class="fixed inset-y-0 left-0 z-40 hidden h-[100dvh] max-h-[100dvh] w-64 grid grid-rows-[auto_minmax(0,1fr)_auto] border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 md:grid">
    <div class="shrink-0 border-b border-gray-200 px-4 py-4 dark:border-gray-800">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
            <x-application-logo class="h-8 w-auto fill-current text-gray-800 dark:text-gray-100" />
            <span class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ config('app.name') }}</span>
        </a>
    </div>
    <nav class="nv-admin-nav-scroll min-h-0 h-full space-y-1 overflow-y-auto overflow-x-hidden overscroll-contain p-3" aria-label="Admin">
        @if($isAdmin)
            @foreach($navItems as $item)
                <x-nav-link :href="$item['route']" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
            @endforeach
        @else
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('site.admin_nav_dashboard') }}
            </x-nav-link>
        @endif
        <x-nav-link :href="route('home', ['locale' => $adminLocale])" :active="false">
            {{ __('site.view_site') }}
        </x-nav-link>
    </nav>
    <div class="shrink-0 space-y-3 border-t border-gray-200 p-3 dark:border-gray-800">
        <button
            type="button"
            data-admin-theme-toggle
            data-label-to-dark="{{ __('site.theme_to_dark') }}"
            data-label-to-light="{{ __('site.theme_to_light') }}"
            class="admin-btn w-full justify-center"
            aria-label="{{ __('site.theme_to_dark') }}"
        >
            <span data-admin-theme-label>{{ __('site.theme_to_dark') }}</span>
        </button>
        @if($isAdmin)
            <select
                id="admin-locale-switcher"
                class="block w-full rounded-md border-gray-300 py-2 pl-2 pr-7 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                onchange="window.location.href=this.value"
            >
                @foreach($adminLocales as $loc)
                    <option value="{{ request()->fullUrlWithQuery(['admin_locale' => $loc]) }}" @selected($adminLocale === $loc)>{{ strtoupper($loc) }}</option>
                @endforeach
            </select>
        @endif
        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ Auth::user()->name }}</p>
            <p class="truncate">{{ Auth::user()->email }}</p>
        </div>
        <div class="flex gap-2">
            <x-admin.btn :href="route('profile.edit')" class="flex-1 justify-center px-2 py-1 text-sm">{{ __('site.common_profile') }}</x-admin.btn>
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit" class="admin-btn w-full justify-center px-2 py-1 text-sm">{{ __('site.common_logout') }}</button>
            </form>
        </div>
    </div>
</aside>

<div id="nv-admin-sidebar-mobile" class="fixed inset-0 z-50 hidden md:hidden" aria-hidden="true">
    <button type="button" class="absolute inset-0 bg-gray-900/50" data-admin-sidebar-close></button>
    <div class="absolute inset-y-0 left-0 grid h-[100dvh] max-h-[100dvh] w-[18rem] grid-rows-[auto_minmax(0,1fr)_auto] border-r border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <span class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
            <button type="button" class="rounded-md p-1 text-gray-600 dark:text-gray-300" data-admin-sidebar-close aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <nav class="nv-admin-nav-scroll min-h-0 h-full space-y-1 overflow-y-auto overflow-x-hidden overscroll-contain p-3">
            @if($isAdmin)
                @foreach($navItems as $item)
                    <x-responsive-nav-link :href="$item['route']" :active="$item['active']">{{ $item['label'] }}</x-responsive-nav-link>
                @endforeach
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('site.admin_nav_dashboard') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('home', ['locale' => $adminLocale])" :active="false">
                {{ __('site.view_site') }}
            </x-responsive-nav-link>
        </nav>
        <div class="shrink-0 space-y-2 border-t border-gray-200 p-3 dark:border-gray-800">
            <button
                type="button"
                data-admin-theme-toggle
                data-label-to-dark="{{ __('site.theme_to_dark') }}"
                data-label-to-light="{{ __('site.theme_to_light') }}"
                class="admin-btn w-full justify-center"
                aria-label="{{ __('site.theme_to_dark') }}"
            >
                <span data-admin-theme-label>{{ __('site.theme_to_dark') }}</span>
            </button>
            @if($isAdmin)
                <select
                    class="block w-full rounded-md border-gray-300 py-2 pl-2 pr-7 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                    onchange="window.location.href=this.value"
                >
                    @foreach($adminLocales as $loc)
                        <option value="{{ request()->fullUrlWithQuery(['admin_locale' => $loc]) }}" @selected($adminLocale === $loc)>{{ strtoupper($loc) }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>
</div>
