@props([
    'seo',
    'variant' => \App\Support\SeoMetaVariant::DEFAULT,
    'includeDescription' => null,
    'includeRobots' => null,
    'includeOpenGraph' => null,
    'includeTwitter' => null,
    'openGraphType' => null,
    'openGraphUrl' => null,
    'twitterCard' => 'summary_large_image',
])

@php
    $preset = \App\Support\SeoMetaVariant::preset((string) $variant, $seo->canonical ?? null);

    $resolvedIncludeDescription = $includeDescription ?? $preset['includeDescription'];
    $resolvedIncludeRobots = $includeRobots ?? $preset['includeRobots'];
    $resolvedIncludeOpenGraph = $includeOpenGraph ?? $preset['includeOpenGraph'];
    $resolvedIncludeTwitter = $includeTwitter ?? $preset['includeTwitter'];
    $resolvedOpenGraphType = $openGraphType ?? $preset['openGraphType'];
    $resolvedOpenGraphUrl = $openGraphUrl ?? $preset['openGraphUrl'];
    $resolvedTwitterCard = $twitterCard ?: $preset['twitterCard'];
@endphp

@foreach($seo->toMetaArray(includeDescription: $resolvedIncludeDescription, includeRobots: $resolvedIncludeRobots) as $name => $content)
    <meta name="{{ $name }}" content="{!! htmlspecialchars(\App\Support\HtmlText::decodeEntitiesForBlade((string) $content), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}">
@endforeach

@if($resolvedIncludeOpenGraph)
    @foreach($seo->toOpenGraphArray(type: $resolvedOpenGraphType, url: $resolvedOpenGraphUrl) as $property => $content)
        <meta property="{{ $property }}" content="{!! htmlspecialchars(\App\Support\HtmlText::decodeEntitiesForBlade((string) $content), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}">
    @endforeach
@endif

@if($resolvedIncludeTwitter)
    @foreach($seo->toTwitterArray(card: $resolvedTwitterCard) as $name => $content)
        <meta name="{{ $name }}" content="{!! htmlspecialchars(\App\Support\HtmlText::decodeEntitiesForBlade((string) $content), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}">
    @endforeach
@endif

