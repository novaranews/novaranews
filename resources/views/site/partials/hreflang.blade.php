@isset($localeSwitchUrls)
    @foreach($localeSwitchUrls as $loc => $url)
        <link rel="alternate" hreflang="{{ $loc }}" href="{{ $url }}">
    @endforeach
    @php $defaultLocale = config('novaranews.default_locale', 'en'); @endphp
    @php
        $xDefaultUrl = $localeSwitchUrls[$defaultLocale] ?? (count($localeSwitchUrls) > 0 ? reset($localeSwitchUrls) : null);
    @endphp
    @if($xDefaultUrl)
        <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}">
    @endif
@endisset
