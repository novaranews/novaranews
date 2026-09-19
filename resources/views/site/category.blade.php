@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@push('meta')
    <x-site.seo-meta :seo="$seo" />
    @if($articles->previousPageUrl())
        @php
            // Page=2's prev must point to the base URL (no ?page param), not ?page=1.
            // The base URL and ?page=1 carry the same content; page=1 now redirects to base.
            // This fixes the "Pagination: Sequence Error" in SEO audits.
            $relPrevUrl = $articles->currentPage() === 2
                ? url()->current()
                : $articles->previousPageUrl();
        @endphp
        <link rel="prev" href="{{ $relPrevUrl }}">
    @endif
    @if($articles->nextPageUrl())
        <link rel="next" href="{{ $articles->nextPageUrl() }}">
    @endif
    @php
        $collectionLd = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $translation->name,
            'description' => $seo->description,
            'url' => url()->current(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => \App\Models\Setting::site('site_name', config('app.name')),
                'url' => url('/'.app()->getLocale()),
            ],
        ];
        $breadcrumbLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => __('site.home_breadcrumb'),
                    'item' => route('home', ['locale' => app()->getLocale()]),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $translation->name,
                    'item' => $seo->canonical,
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($collectionLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
@php
    $catColorMap = [
        'world' => 'bg-blue-600',
        'politics' => 'bg-red-600',
        'business' => 'bg-emerald-600',
        'technology' => 'bg-violet-600',
        'artificial-intelligence' => 'bg-fuchsia-600',
        'sports' => 'bg-orange-500',
        'health' => 'bg-rose-500',
        'science' => 'bg-cyan-600',
        'environment' => 'bg-green-600',
    ];
    $catColor = fn (?string $key): string => $catColorMap[$key ?? ''] ?? 'bg-stone-600';
    $avatarColors = ['bg-novara-800', 'bg-blue-600', 'bg-emerald-600', 'bg-violet-600', 'bg-teal-600', 'bg-rose-500'];
    $avatarBg = fn (?string $name): string => $name ? $avatarColors[abs(crc32($name)) % count($avatarColors)] : 'bg-novara-800';
    $authorInitials = fn (?string $name): string => $name
        ? collect(explode(' ', trim($name)))->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->join('')
        : 'N';
    $readTime = fn (string $body): int => max(1, (int) ceil(str_word_count(strip_tags($body)) / 220));
@endphp
<div class="nv-container py-8 sm:py-10">
    <h1 class="nv-section-title">{!! htmlspecialchars($translation->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</h1>
    <p class="mt-3 max-w-3xl text-base leading-relaxed text-stone-600 dark:text-stone-400">{!! htmlspecialchars($seo->intro, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>

    {{-- Featured card: fetched separately by the controller so it never occupies a grid slot --}}
    @if($featuredArticle)
        @php $ft = $featuredArticle->translate(); $fUrl = $featuredArticle->publicUrl(); @endphp
        @if($ft)
            <div class="mt-8">
                @php $fpick = $featuredArticle->is_editors_pick ?? false; $fep = $fpick ? ' border-l-4 border-l-amber-500' : ''; @endphp
                @if($fUrl)
                <a href="{{ $fUrl }}" class="nv-surface-card group grid gap-6 overflow-hidden rounded-2xl lg:grid-cols-[1.2fr_1fr]{{ $fep }}">
                @else
                <div class="nv-surface-card grid gap-6 overflow-hidden rounded-2xl lg:grid-cols-[1.2fr_1fr]{{ $fep }}">
                @endif
                    <div class="flex aspect-video max-h-[min(46vh,360px)] min-h-[170px] w-full items-center justify-center overflow-hidden bg-stone-200 dark:bg-stone-800 sm:min-h-[200px] lg:max-h-[min(50vh,420px)] lg:min-h-[240px]">
                        @if($featuredArticle->featured_image)
                            @include('site.partials.article-img', [
                                'imgArticle'  => $featuredArticle,
                                'imgAlt'      => htmlspecialchars($featuredArticle->featured_image_alt ?: $ft->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                'imgLoading'  => 'eager',
                                'imgSizes'    => '(max-width: 1024px) 100vw, 50vw',
                            ])
                        @endif
                    </div>
                    <div class="flex flex-col justify-center p-5 lg:py-8 lg:pr-8 lg:pl-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="nv-news-kicker !border-stone-300 !bg-stone-100 !text-stone-700 dark:!border-stone-600 dark:!bg-stone-800 dark:!text-stone-200">{{ __('site.latest') }}</span>
                            @include('site.partials.content-type-badge', ['article' => $featuredArticle])
                        </div>
                        <h2 class="mt-2 font-serif text-2xl font-bold leading-tight tracking-tight text-stone-900 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400 sm:text-3xl">{!! htmlspecialchars($ft->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</h2>
                        @if($ft->excerpt)
                            <p class="mt-3 line-clamp-3 text-base leading-relaxed text-stone-600 dark:text-stone-400">{!! htmlspecialchars($ft->excerpt, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                        @endif
                        <p class="mt-4 text-sm tabular-nums text-stone-500 dark:text-stone-500">{{ $featuredArticle->published_at?->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                @if($fUrl)</a>@else</div>@endif
            </div>
        @endif
    @endif

    {{-- Grid: always receives exactly $perPage articles — no missing slots --}}
    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($articles as $article)
            @include('site.partials.article-card', ['a' => $article, 'catColor' => $catColor, 'authorInitials' => $authorInitials, 'avatarBg' => $avatarBg, 'readTime' => $readTime])
        @endforeach
    </div>
    <div class="mt-10">{{ $articles->links() }}</div>
</div>
@endsection
