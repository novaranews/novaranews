@php
    $gaId      = \App\Models\Setting::site('ga4_measurement_id', config('novaranews.ga4_measurement_id'));
    $gaEnabled = (bool) \App\Models\Setting::site('ga4_enabled', config('novaranews.ga4_enabled'));
@endphp
@if($gaEnabled && filled($gaId))
{{-- GA4 — cookie onayı sonrası window._loadGA4() çağrısıyla yüklenir --}}
<script>
window._ga4Id = "{{ e($gaId) }}";
window._loadGA4 = function () {
    if (window._ga4Loaded) return;
    window._ga4Loaded = true;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + window._ga4Id;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', window._ga4Id);
};
</script>
@endif
