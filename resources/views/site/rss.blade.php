<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:media="http://search.yahoo.com/mrss/"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ config('app.name') }} - {{ strtoupper($locale) }}</title>
        <link>{{ route('home', ['locale' => $locale]) }}</link>
        <description>{{ __('site.rss_description', ['site' => config('app.name'), 'locale' => strtoupper($locale)]) }}</description>
        <language>{{ $locale }}</language>
        <atom:link href="{{ route('feed', ['locale' => $locale]) }}" rel="self" type="application/rss+xml"/>
        <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
        <ttl>15</ttl>
@foreach($articles as $article)
@php
    $itemUrl = $article->publicUrl($locale);
    $tr = $article->translations->firstWhere('locale', $locale);
    $catTr = $article->category?->translations->firstWhere('locale', $locale);
    $imageUrl = $article->featured_image
        ? url(\Illuminate\Support\Facades\Storage::url($article->featured_image))
        : null;
@endphp
@if($itemUrl && $tr)
        <item>
            <title><![CDATA[{!! \App\Support\HtmlText::decodeEntitiesForBlade($tr->title) !!}]]></title>
            <link>{{ $itemUrl }}</link>
            <guid isPermaLink="true">{{ $itemUrl }}</guid>
            <pubDate>{{ $article->published_at?->toRssString() ?? $article->updated_at->toRssString() }}</pubDate>
            @if($tr->excerpt)
            <description><![CDATA[{!! \App\Support\HtmlText::decodeEntitiesForBlade($tr->excerpt) !!}]]></description>
            @endif
            @if($tr->body)
            <content:encoded><![CDATA[{!! $tr->body !!}]]></content:encoded>
            @endif
            @if($catTr)
            <category>{{ $catTr->name }}</category>
            @endif
            @if($article->author?->name)
            <dc:creator><![CDATA[{!! \App\Support\HtmlText::decodeEntitiesForBlade($article->author->name) !!}]]></dc:creator>
            @endif
            @if($imageUrl)
            <enclosure url="{{ $imageUrl }}" type="image/jpeg" length="0"/>
            <media:content url="{{ $imageUrl }}" medium="image">
                @if($article->featured_image_alt)
                <media:description type="plain"><![CDATA[{!! \App\Support\HtmlText::decodeEntitiesForBlade($article->featured_image_alt) !!}]]></media:description>
                @endif
            </media:content>
            @endif
        </item>
@endif
@endforeach
    </channel>
</rss>
