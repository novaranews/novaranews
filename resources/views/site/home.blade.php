@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('meta')
    <x-site.seo-meta :seo="$seo" />
    {{-- Hero görselini preload — LCP'yi hızlandırır.
         imagesrcset/imagesizes eklenerek mobilde yanlış boyutun preload edilmesi önlenir. --}}
    @if(isset($hero) && $hero && $hero->featured_image)
        @php
            $__heroSrc   = Storage::url($hero->featured_image);
            $__heroThumb = $hero->featured_image_thumb ? Storage::url($hero->featured_image_thumb) : null;
        @endphp
        @if($__heroThumb)
            <link rel="preload" as="image"
                  href="{{ $__heroSrc }}"
                  imagesrcset="{{ $__heroThumb }} {{ \App\Services\ImageOptimizerService::THUMB_WIDTH }}w, {{ $__heroSrc }} 1920w"
                  imagesizes="100vw">
        @else
            <link rel="preload" as="image" href="{{ $__heroSrc }}">
        @endif
    @endif
    @php
        $orgId  = url('/#organization');
        $siteId = url('/'.app()->getLocale()).'#website';
        $schemaSiteName = \App\Support\HtmlText::decodeEntitiesForBlade((string) \App\Models\Setting::site('site_name', config('app.name')));
        $searchTarget = route('search', ['locale' => app()->getLocale()], true).'?q={search_term_string}';
        $graph  = [
            ['@type' => 'WebSite',  '@id' => $siteId,  'name' => $schemaSiteName, 'url' => url('/'.app()->getLocale()), 'inLanguage' => app()->getLocale(), 'publisher' => ['@id' => $orgId], 'potentialAction' => ['@type' => 'SearchAction', 'target' => $searchTarget, 'query-input' => 'required name=search_term_string']],
            ['@type' => 'NewsMediaOrganization', '@id' => $orgId, 'name' => $schemaSiteName, 'url' => url('/')],
        ];
        if ($logo = \App\Models\Setting::site('publisher_logo_url', \App\Models\Setting::site('site_logo_url', config('novaranews.publisher_logo_url')))) {
            $graph[1]['logo'] = ['@type' => 'ImageObject', 'url' => $logo];
        }
    @endphp
    <script type="application/ld+json">
        {!! json_encode(['@'.'context' => 'https://schema.org', '@'.'graph' => $graph], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@section('content')
@php
    /* ── Kategori renk haritası ────────────────────────────── */
    $catColorMap = [
        'world'                   => 'bg-novara-800',
        'politics'                => 'bg-novara-800',
        'business'                => 'bg-stone-700',
        'technology'              => 'bg-stone-700',
        'artificial-intelligence' => 'bg-novara-800',
        'sports'                  => 'bg-stone-700',
        'health'                  => 'bg-stone-700',
        'science'                 => 'bg-novara-800',
        'environment'             => 'bg-stone-700',
    ];
    $catColor = fn (?string $key): string => $catColorMap[$key ?? ''] ?? 'bg-stone-700';

    /* ── Yazar avatar yardımcıları ─────────────────────────── */
    $avatarColors    = ['bg-novara-800', 'bg-blue-600', 'bg-emerald-600', 'bg-violet-600', 'bg-teal-600', 'bg-rose-500'];
    $avatarBg        = fn (?string $name): string => $name
        ? $avatarColors[abs(crc32($name)) % count($avatarColors)]
        : 'bg-novara-800';
    $authorInitials  = fn (?string $name): string => $name
        ? collect(explode(' ', trim($name)))->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->join('')
        : 'N';

    /* ── Okuma süresi ──────────────────────────────────────── */
    $readTime = fn (string $body): int => max(1, (int) ceil(str_word_count(strip_tags($body)) / 220));
    $latestUpdatedAt = $side->first()?->published_at ?? $hero?->published_at ?? null;
@endphp

<div class="nv-container py-8 sm:py-10">

    {{-- ══════════════════════════════════════════════════════
         HERO BOLUMU  -  Buyuk featured + 3 yan makale
    ═══════════════════════════════════════════════════════ --}}
    <div class="nv-reveal grid gap-5 lg:grid-cols-[1.6fr_1fr]">

        {{-- ── Büyük Hero Kartı ── --}}
        @if($hero)
            @php $ht = $hero->translate(); $hc = $hero->category?->translate(); $heroUrl = $hero->publicUrl(); @endphp
            @if($ht && $hc)
            <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-transparent shadow-xl ring-1 ring-stone-900/10 min-h-[300px] max-h-[420px] sm:min-h-[360px] sm:max-h-[480px] lg:max-h-[520px]">
                <div class="relative min-h-[300px] flex-1 w-full overflow-hidden bg-transparent sm:min-h-0">
                    @if($hero->featured_image)
                        @include('site.partials.article-img', [
                            'imgArticle'  => $hero,
                            'imgAlt'      => htmlspecialchars($hero->featured_image_alt ?: $ht->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                            'imgClass'    => 'block h-full w-full object-cover transition-transform duration-500',
                            'imgLoading'  => 'eager',
                            'imgSizes'    => '100vw',
                        ])
                    @else
                        <div class="flex min-h-[220px] w-full items-center justify-center bg-stone-800 py-16">
                            <svg class="h-16 w-16 text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 15l-5-6-4 5-3-3-5 6"/></svg>
                        </div>
                    @endif
                </div>

                {{-- Text overlay --}}
                <div class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-black/85 via-black/50 to-transparent pt-16 p-4 sm:p-5 lg:p-6">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="hidden sm:inline-flex nv-news-kicker">{{ __('site.latest') }}</span>
                        @include('site.partials.content-type-badge', ['article' => $hero, 'variant' => 'hero'])
                        <span class="inline-block rounded-sm px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white {{ $catColor($hero->category?->key) }}">
                            {{ $hc->name }}
                        </span>
                    </div>
                    <h1 class="nv-hero-title line-clamp-2">
                        @if($heroUrl)
                            <a href="{{ $heroUrl }}" class="transition-colors hover:text-sky-300">{!! htmlspecialchars($ht->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</a>
                        @else
                            {!! htmlspecialchars($ht->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                        @endif
                    </h1>
                    @if($ht->excerpt)
                        <p class="nv-hero-excerpt mt-2 line-clamp-2">{!! htmlspecialchars($ht->excerpt, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                    @endif
                    <div class="nv-hero-meta mt-2 flex items-center gap-2 text-xs text-stone-300 sm:mt-3">
                        @if($hero->author)
                            @if(!empty($hero->author->avatar_url))
                                <img
                                    src="{{ $hero->author->avatar_url }}"
                                    alt="{{ $hero->author->name }}"
                                    class="nv-avatar-sm hidden ring-white/30 sm:block"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @else
                                <span class="{{ $avatarBg($hero->author->name) }} nv-avatar-fallback-sm hidden sm:inline-flex">{{ $authorInitials($hero->author->name) }}</span>
                            @endif
                            <span class="font-medium text-stone-300">{{ $hero->author->name }}</span>
                            <span aria-hidden="true">·</span>
                        @endif
                        <time>{{ $hero->published_at?->diffForHumans() }}</time>
                        @if($ht->body)
                            <span aria-hidden="true" class="hidden sm:inline">·</span>
                            <span class="hidden tabular-nums sm:inline">{{ __('site.min_read', ['n' => (string) $readTime($ht->body)]) }}</span>
                        @endif
                    </div>
                </div>
            </article>
            @endif
        @endif

        {{-- ── Yan Makaleler (3 yatay kart) ── --}}
        <div class="flex flex-col gap-3">
            <div class="mb-1 flex items-center justify-between gap-3">
                @include('site.partials.section-header', ['title' => __('site.latest'), 'class' => 'mb-0'])
                @if($latestUpdatedAt)
                    <span class="shrink-0 text-[11px] font-medium text-stone-500 dark:text-stone-400">{{ __('site.updated') }} {{ $latestUpdatedAt->diffForHumans() }}</span>
                @endif
            </div>
            @foreach($side as $a)
                @php $st = $a->translate(); $sc = $a->category?->translate(); $sideUrl = $a->publicUrl(); @endphp
                @if($st && $sc)
                    @if($sideUrl)
                    <a href="{{ $sideUrl }}"
                       class="nv-surface-card group flex min-h-[96px] gap-3.5 overflow-hidden p-3">
                    @else
                    <div class="nv-surface-card flex min-h-[96px] gap-3.5 overflow-hidden p-3">
                    @endif
                        <div class="-my-3 -ml-3 w-[144px] shrink-0 self-stretch overflow-hidden rounded-l-xl sm:w-[160px]">
                            @if($a->featured_image)
                                @include('site.partials.article-img', [
                                    'imgArticle' => $a,
                                    'imgAlt'     => htmlspecialchars($a->featured_image_alt ?: $st->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                    'imgClass'   => 'h-full w-full object-cover',
                                    'imgSizes'   => '160px',
                                ])
                            @else
                                <div class="flex h-full items-center justify-center">
                                    <svg class="h-8 w-8 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 15l-5-6-4 5-3-3-5 6"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex min-w-0 flex-col justify-center">
                            <div class="flex flex-wrap items-center gap-1.5">
                                @include('site.partials.content-type-badge', ['article' => $a])
                                <span class="inline-block w-fit rounded-sm px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white {{ $catColor($a->category?->key) }}">{{ $sc->name }}</span>
                            </div>
                            <h2 class="mt-1.5 font-sans text-[14px] font-semibold leading-snug tracking-tight text-stone-900 line-clamp-3 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400 sm:text-[15px]">
                                {!! htmlspecialchars($st->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                            </h2>
                            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-stone-400 dark:text-stone-500">
                                @if($a->author)
                                    @if(!empty($a->author->avatar_url))
                                        <img
                                            src="{{ $a->author->avatar_url }}"
                                            alt="{{ $a->author->name }}"
                                            class="nv-avatar-xs"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @else
                                        <span class="{{ $avatarBg($a->author->name) }} nv-avatar-fallback-xs">{{ $authorInitials($a->author->name) }}</span>
                                    @endif
                                @endif
                                <time>{{ $a->published_at?->diffForHumans() }}</time>
                            </div>
                        </div>
                    @if($sideUrl)</a>@else</div>@endif
                @endif
            @endforeach
        </div>
    </div>{{-- /hero grid --}}

    @if(isset($aiCategory, $aiSpotlight) && $aiSpotlight->isNotEmpty())
        @php $aiCatTr = $aiCategory->translate(); @endphp
        @if($aiCatTr)
        <div
            class="nv-reveal mt-10 rounded-xl border border-violet-200/90 bg-gradient-to-br from-violet-50/90 to-white p-4 dark:border-violet-900/60 dark:from-violet-950/40 dark:to-stone-900/40"
        >
            <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-800 dark:text-fuchsia-300/90">
                    <span class="h-3 w-0.5 rounded-full bg-fuchsia-600 dark:bg-fuchsia-400"></span>
                    {{ __('site.home_ai_spotlight') }}
                </h2>
                <a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $aiCatTr->slug]) }}"
                   class="shrink-0 text-xs font-semibold text-violet-700 transition hover:underline dark:text-fuchsia-300/90 dark:hover:text-fuchsia-200">
                    {{ __('site.home_ai_spotlight_all') }} →
                </a>
            </div>
            <div class="relative">
                @if($aiSpotlight->count() > 1)
                    <x-site.btn
                        type="button"
                        variant="carousel-nav-accent"
                        class="left-0 sm:left-0.5"
                        data-nv-hscroll="nv-strip-ai-spotlight"
                        data-nv-hscroll-dir="-1"
                        aria-label="{{ __('site.card_strip_scroll_prev') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </x-site.btn>
                    <x-site.btn
                        type="button"
                        variant="carousel-nav-accent"
                        class="right-0 sm:right-0.5"
                        data-nv-hscroll="nv-strip-ai-spotlight"
                        data-nv-hscroll-dir="1"
                        aria-label="{{ __('site.card_strip_scroll_next') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </x-site.btn>
                @endif
                <div
                    id="nv-strip-ai-spotlight"
                    class="w-full min-w-0 overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    tabindex="0"
                    role="region"
                    aria-label="{{ __('site.home_ai_spotlight') }}"
                >
                    <ul class="flex w-max min-w-full justify-start gap-4 pr-2 snap-x snap-mandatory">
                        @foreach($aiSpotlight as $aiA)
                            @php $ait = $aiA->translate(); $aiu = $aiA->publicUrl(); @endphp
                            @if($ait && $aiu)
                            <li class="w-[min(85vw,240px)] shrink-0 snap-start">
                                <a href="{{ $aiu }}" class="group flex h-full flex-col overflow-hidden rounded-lg border border-violet-200/80 bg-white shadow-sm transition hover:border-fuchsia-300/80 hover:shadow-md dark:border-violet-900/60 dark:bg-stone-900 dark:hover:border-fuchsia-700/50">
                                    <div class="flex aspect-[5/3] items-center justify-center overflow-hidden bg-violet-100/80 dark:bg-stone-800">
                                        @if($aiA->featured_image)
                                            @include('site.partials.article-img', [
                                                'imgArticle' => $aiA,
                                                'imgAlt'     => htmlspecialchars($aiA->featured_image_alt ?: $ait->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                                'imgSizes'   => '240px',
                                            ])
                                        @else
                                            <svg class="h-8 w-8 text-violet-300 dark:text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 15l-5-6-4 5-3-3-5 6"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex flex-1 flex-col p-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wide text-fuchsia-700 dark:text-fuchsia-400">{{ $aiCatTr->name }}</span>
                                        <p class="mt-1 line-clamp-3 text-sm font-semibold leading-snug text-stone-900 group-hover:text-violet-800 dark:text-stone-100 dark:group-hover:text-fuchsia-300">{!! htmlspecialchars($ait->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                        <time class="mt-auto pt-2 text-[11px] text-stone-400">{{ $aiA->published_at?->diffForHumans() }}</time>
                                    </div>
                                </a>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif
    @endif

    {{-- ══════════════════════════════════════════════════════
         ANA İÇERİK + SIDEBAR
    ═══════════════════════════════════════════════════════ --}}
    <div class="nv-reveal mt-12 items-start gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_320px]">

        <div class="min-w-0">
        @if($latest->isNotEmpty())
            @include('site.partials.section-header', ['title' => __('site.latest'), 'class' => 'mb-0'])
            <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($latest as $a)
                    @include('site.partials.article-card', ['a' => $a, 'catColor' => $catColor, 'authorInitials' => $authorInitials, 'avatarBg' => $avatarBg, 'readTime' => $readTime])
                @endforeach
            </div>
        @endif

        @if(isset($hotToday) && $hotToday->isNotEmpty())
        <div
            class="mt-10 rounded-xl border border-stone-200 bg-stone-50/80 p-4 dark:border-stone-700/60 dark:bg-stone-900/40"
        >
            @include('site.partials.section-header', ['title' => __('site.home_hot_today')])
            {{-- Tam genişlik şerit + kartlar sola yaslı; oklar üstte (layout sütunu yok) --}}
            <div class="relative">
                @if($hotToday->count() > 1)
                    <x-site.btn
                        type="button"
                        variant="carousel-nav"
                        class="left-0 sm:left-0.5"
                        data-nv-hscroll="nv-strip-hot-today"
                        data-nv-hscroll-dir="-1"
                        aria-label="{{ __('site.card_strip_scroll_prev') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </x-site.btn>
                    <x-site.btn
                        type="button"
                        variant="carousel-nav"
                        class="right-0 sm:right-0.5"
                        data-nv-hscroll="nv-strip-hot-today"
                        data-nv-hscroll-dir="1"
                        aria-label="{{ __('site.card_strip_scroll_next') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </x-site.btn>
                @endif
                <div
                    id="nv-strip-hot-today"
                    class="w-full min-w-0 overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    tabindex="0"
                    role="region"
                    aria-label="{{ __('site.home_hot_today') }}"
                >
                <ul class="flex w-max min-w-full justify-start gap-4 pr-2 snap-x snap-mandatory">
                    @foreach($hotToday as $ha)
                        @php $htr = $ha->translate(); $hcc = $ha->category?->translate(); $hU = $ha->publicUrl(); @endphp
                        @if($htr && $hU)
                        <li class="w-[min(85vw,240px)] shrink-0 snap-start">
                            <a href="{{ $hU }}" class="group flex h-full flex-col overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm transition hover:border-stone-300 hover:shadow-md dark:border-stone-700 dark:bg-stone-900 dark:hover:border-stone-600">
                                <div class="flex aspect-[5/3] items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-800">
                                    @if($ha->featured_image)
                                        @include('site.partials.article-img', [
                                            'imgArticle' => $ha,
                                            'imgAlt'     => htmlspecialchars($ha->featured_image_alt ?: $htr->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                            'imgSizes'   => '240px',
                                        ])
                                    @else
                                        <svg class="h-8 w-8 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 15l-5-6-4 5-3-3-5 6"/></svg>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col p-3">
                                    @if($hcc)
                                        <span class="text-[10px] font-bold uppercase tracking-wide text-novara-800 dark:text-sky-400">{{ $hcc->name }}</span>
                                    @endif
                                    <p class="mt-1 line-clamp-3 text-sm font-semibold leading-snug text-stone-900 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400">{!! htmlspecialchars($htr->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                    <time class="mt-auto pt-2 text-[11px] text-stone-400">{{ $ha->published_at?->diffForHumans() }}</time>
                                </div>
                            </a>
                        </li>
                        @endif
                    @endforeach
                </ul>
                </div>
            </div>
        </div>
        @endif

        </div>{{-- /main column --}}

        {{-- ── SIDEBAR ── --}}
        <aside class="mt-10 space-y-8 lg:mt-0">

            {{-- Numaralı Trending Listesi --}}
            @if($sidebarTrending->isNotEmpty())
            <div>
                @include('site.partials.section-header', ['title' => __('site.trending'), 'class' => 'mb-0'])
                <ol class="mt-4 divide-y divide-stone-100 dark:divide-stone-800">
                    @foreach($sidebarTrending as $i => $a)
                        @php $st = $a->translate(); $sc = $a->category?->translate(); $sUrl = $a->publicUrl(); @endphp
                        @if($st)
                        <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="w-6 shrink-0 text-right text-2xl font-extrabold tabular-nums leading-none text-stone-200 dark:text-stone-700 select-none">{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                @if($sc)
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-novara-800 dark:text-sky-400">{{ $sc->name }}</span>
                                @endif
                                @if($sUrl)
                                <a href="{{ $sUrl }}" class="mt-0.5 block text-sm font-semibold leading-snug text-stone-800 line-clamp-2 hover:text-novara-800 dark:text-stone-200 dark:hover:text-sky-400">
                                    {!! htmlspecialchars($st->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                                </a>
                                @else
                                <p class="mt-0.5 text-sm font-semibold leading-snug text-stone-800 line-clamp-2 dark:text-stone-200">{!! htmlspecialchars($st->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                @endif
                                <time class="mt-1 block text-[11px] text-stone-400">{{ $a->published_at?->diffForHumans() }}</time>
                            </div>
                        </li>
                        @endif
                    @endforeach
                </ol>
            </div>
            @endif

            {{-- Editörün Seçimi --}}
            @if($editorsPicks->isNotEmpty())
            <div>
                @include('site.partials.section-header', ['title' => __('site.editors_picks'), 'class' => 'mb-0'])
                <div class="mt-4 space-y-4">
                    @foreach($editorsPicks as $a)
                        @php $et = $a->translate(); $ec = $a->category?->translate(); $eUrl = $a->publicUrl(); @endphp
                        @if($et)
                        @if($eUrl)<a href="{{ $eUrl }}" class="group flex gap-3">@else<div class="flex gap-3">@endif
                            <div class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-100 dark:bg-stone-800">
                                @if($a->featured_image)
                                    @include('site.partials.article-img', [
                                        'imgArticle' => $a,
                                        'imgAlt'     => $et->title,
                                        'imgSizes'   => '96px',
                                    ])
                                @else
                                    <svg class="h-7 w-7 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 15l-5-6-4 5-3-3-5 6"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                @if($ec)
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-novara-800 dark:text-sky-400">{{ $ec->name }}</span>
                                @endif
                                <p class="mt-0.5 text-sm font-semibold leading-snug text-stone-800 line-clamp-2 group-hover:text-novara-800 dark:text-stone-200 dark:group-hover:text-sky-400">
                                    {!! htmlspecialchars($et->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                                </p>
                            </div>
                        @if($eUrl)</a>@else</div>@endif
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Kategori Listesi --}}
            @if(($navCategories ?? collect())->isNotEmpty())
            <div>
                @include('site.partials.section-header', ['title' => __('site.nav_primary'), 'class' => 'mb-0'])
                <div class="mt-3 space-y-0.5">
                    @foreach($navCategories as $cat)
                        @php $ctr = $cat->translate(); @endphp
                        @if($ctr)
                        <a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $ctr->slug]) }}"
                           class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition-colors hover:bg-stone-100 hover:text-novara-800 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-sky-400">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $catColor($cat->key) }}"></span>
                            {{ $ctr->name }}
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

        </aside>{{-- /sidebar --}}

    </div>{{-- /main+sidebar grid --}}


    {{-- ══════════════════════════════════════════════════════
         KATEGORI BOLUMLERI - esit kartli izgara (1 / 2 / 3 sutun)
    ═══════════════════════════════════════════════════════ --}}
    @if(isset($categorySections))
        @foreach($categorySections as $section)
            @php
                $catTr    = $section['category']->translate();
                $articles = $section['articles'];
            @endphp
            @if($catTr && $articles->isNotEmpty())
            <div class="nv-reveal mt-14">

                @include('site.partials.section-header', [
                    'title' => $catTr->name,
                    'url' => route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $catTr->slug]),
                    'linkLabel' => __('site.all_in_category', ['category' => $catTr->name]),
                    'class' => 'mb-5',
                ])

                <div class="rounded-xl border border-stone-200/80 bg-stone-50/40 p-3 sm:p-4 dark:border-stone-700/50 dark:bg-stone-900/20">
                    <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-3">
                        @foreach($articles as $a)
                            @include('site.partials.article-card', [
                                'a'               => $a,
                                'catColor'        => $catColor,
                                'authorInitials'  => $authorInitials,
                                'avatarBg'        => $avatarBg,
                                'readTime'        => $readTime,
                                'cardShowExcerpt' => false,
                            ])
                        @endforeach
                    </div>
                </div>

            </div>
            @endif
        @endforeach
    @endif

</div>{{-- /max-w-6xl --}}
@endsection
