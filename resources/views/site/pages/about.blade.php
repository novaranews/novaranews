@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@push('meta')
    <x-site.seo-meta :seo="$seo" />
@endpush

@section('content')
<div class="nv-container max-w-3xl py-10">
    <h1 class="font-serif text-3xl font-bold text-stone-900 dark:text-stone-100">{{ $pageHeading }}</h1>
    <div class="nv-static-prose mt-6">
        {!! $pageContent !!}
    </div>
</div>
@endsection
