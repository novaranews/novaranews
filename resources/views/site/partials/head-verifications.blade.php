@php
    $gsc           = trim((string) \App\Models\Setting::site('google_site_verification', config('novaranews.google_site_verification', '')));
    $adsenseOn     = (bool) \App\Models\Setting::site('adsense_enabled', config('novaranews.adsense_enabled'));
    $adsenseClient = trim((string) \App\Models\Setting::site('adsense_client_id', config('novaranews.adsense_client_id', '')));
@endphp
@if($gsc !== '')
    <meta name="google-site-verification" content="{{ e($gsc) }}">
@endif
@if($adsenseOn && $adsenseClient !== '')
<script>
(function () {
    var CONSENT_KEY = 'novara_consent';
    var consent = null;
    var ads = window.adsbygoogle = window.adsbygoogle || [];

    try { consent = localStorage.getItem(CONSENT_KEY); } catch (e) {}

    if (! consent) {
        ads.pauseAdRequests = 1;
    }

    ads.requestNonPersonalizedAds = consent === 'all' ? 0 : 1;

    window._setAdSenseConsent = function (type) {
        var nextAds = window.adsbygoogle = window.adsbygoogle || [];
        nextAds.requestNonPersonalizedAds = type === 'all' ? 0 : 1;
        nextAds.pauseAdRequests = 0;
    };
}());
</script>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ e($adsenseClient) }}" crossorigin="anonymous"></script>
@endif
