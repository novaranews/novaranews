@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@push('meta')
    <x-site.seo-meta :seo="$seo" :variant="\App\Support\SeoMetaVariant::LISTING" />
@endpush

@section('content')
<div class="nv-container max-w-4xl py-8 sm:py-10">
    <h1 class="nv-section-title">{{ __('site.authors_title') }}</h1>
    <p class="mt-3 text-base leading-relaxed text-stone-600 dark:text-stone-400">{{ __('site.authors_intro') }}</p>
    <ul class="mt-10 space-y-4">
        @foreach($authors as $author)
            <li>
                <a href="{{ $author->profileUrl() }}" class="nv-surface-card group flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
                    <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" width="96" height="96" class="nv-avatar-lg" loading="lazy" decoding="async">
                    <div class="min-w-0">
                        <h2 class="font-serif text-xl font-semibold tracking-tight text-stone-900 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400">{!! htmlspecialchars($author->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</h2>
                        @if($author->profileTitleForLocale())
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ $author->profileTitleForLocale() }}</p>
                        @endif
                        @if($author->profileBioForLocale())
                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-stone-600 dark:text-stone-300">{{ \Illuminate\Support\Str::limit(strip_tags(\App\Support\HtmlText::decodeEntitiesForBlade($author->profileBioForLocale())), 220) }}</p>
                        @endif
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
    @if($authors->isEmpty())
        <p class="mt-8 text-stone-600 dark:text-stone-400">{{ __('site.authors_empty') }}</p>
    @endif
</div>
@endsection
