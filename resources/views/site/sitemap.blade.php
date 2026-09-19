<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <lastmod>{{ $url['lastmod'] }}</lastmod>
@if(!empty($url['changefreq']))
        <changefreq>{{ $url['changefreq'] }}</changefreq>
@endif
@if(!empty($url['priority']))
        <priority>{{ $url['priority'] }}</priority>
@endif
@if(!empty($url['image_loc']))
        <image:image>
            <image:loc>{{ $url['image_loc'] }}</image:loc>
@if(!empty($url['image_title']))
            <image:title><![CDATA[{!! \App\Support\HtmlText::decodeEntitiesForBlade($url['image_title']) !!}]]></image:title>
@endif
        </image:image>
@endif
    </url>
@endforeach
</urlset>
