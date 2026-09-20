@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('meta')
    <meta name="robots" content="noindex,follow">
    <x-site.seo-meta :seo="$seo" :variant="\App\Support\SeoMetaVariant::LISTING" />
@endpush

{{-- Search pages are noindex; hreflang on noindex pages is ignored by search engines
     and causes "Hreflang: Noindex Return Links" errors in SEO audits. --}}

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
    <h1 class="nv-section-title">{{ __('site.search_heading') }}</h1>

    <form action="{{ route('search', ['locale' => app()->getLocale()]) }}" method="get" class="mt-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-900">
        <div class="flex gap-3">
            <input type="text" name="q" value="{{ $query }}" placeholder="{{ __('site.search_placeholder') }}" required minlength="2"
                   class="flex-1 rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
            <x-site.btn type="submit" variant="primary">{{ __('site.search_button') }}</x-site.btn>
        </div>
    </form>

    @if($query)
        @if($articles instanceof \Illuminate\Pagination\LengthAwarePaginator && $articles->total() > 0)
            <p class="mt-6 text-sm text-stone-600 dark:text-stone-400">{{ __('site.search_results_count', ['count' => $articles->total(), 'query' => $query]) }}</p>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($articles as $article)
                    @include('site.partials.article-card', ['a' => $article, 'catColor' => $catColor, 'authorInitials' => $authorInitials, 'avatarBg' => $avatarBg, 'readTime' => $readTime])
                @endforeach
            </div>
            <div class="mt-10">{{ $articles->links() }}</div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-8 text-center dark:border-stone-700 dark:bg-stone-900/40">
                <div class="text-5xl">🔍</div>
                <p class="mt-3 text-lg font-semibold text-stone-700 dark:text-stone-300">{{ __('site.search_no_results', ['query' => $query]) }}</p>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ __('site.search_try_keywords') }}</p>
                @php $navCats = \App\Models\Category::with('translations')->get(); @endphp
                @if($navCats->isNotEmpty())
                    <div class="mt-6 flex flex-wrap justify-center gap-2">
                        @foreach($navCats as $cat)
                            @php $ctr = $cat->translate(app()->getLocale()); @endphp
                            @if($ctr)
                                <x-site.btn :href="route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $ctr->slug])" variant="chip">
                                    {!! htmlspecialchars($ctr->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                                </x-site.btn>
                            @endif
                        @endforeach
                    </div>
                @endif
                <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="mt-4 inline-block text-sm font-medium text-novara-800 underline underline-offset-2 hover:text-novara-900 dark:text-sky-400 dark:hover:text-sky-300">
                    {{ __('site.search_back_home') }}
                </a>
            </div>
        @endif
    @else
        {{-- No search has been submitted yet. --}}
        <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-stone-50/70 p-8 text-center text-stone-400 dark:border-stone-700 dark:bg-stone-900/40 dark:text-stone-500">
            <p class="text-4xl">📰</p>
            <p class="mt-3 text-sm">{{ __('site.search_type_hint') }}</p>
        </div>
    @endif
</div>
@endsection
