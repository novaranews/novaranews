{{-- Update the read-only canonical_url when content type, locale, or slug changes. --}}
@php
    $baseUrl = rtrim(url('/'), '/');
    $defaultLocale = config('novaranews.default_locale', 'en');
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
    $canonicalSyncPayload = json_encode([
        'prefixes' => config('novaranews.article_path_segment_by_content_type'),
        'base' => $baseUrl,
        'fixedLocale' => $fixedLocale ?? null,
        'defaultLocale' => $defaultLocale,
    ], $jsonFlags);
@endphp
<script type="application/json" id="nv-article-canonical-cfg">{!! $canonicalSyncPayload !!}</script>
<script>
(function () {
    var cfg = JSON.parse(document.getElementById('nv-article-canonical-cfg').textContent);
    var prefixes = cfg.prefixes;
    var base = cfg.base;
    var fixedLocale = cfg.fixedLocale;
    var defaultLocale = cfg.defaultLocale;

    function syncArticleCanonical() {
        var typeEl = document.getElementById('content_type');
        var slugEl = document.getElementById('slug');
        var localeEl = document.getElementById('locale');
        var out = document.getElementById('canonical_url');
        if (!typeEl || !slugEl || !out) {
            return;
        }
        var loc = fixedLocale || (localeEl && localeEl.value) || defaultLocale;
        var slug = (slugEl.value || '').trim();
        var ct = typeEl.value || 'news';
        var map = (prefixes && prefixes[ct]) ? prefixes[ct] : {};
        var prefix = (map && map[loc]) ? map[loc] : ((prefixes && prefixes.news && prefixes.news[loc]) ? prefixes.news[loc] : 'news');
        out.value = slug ? (base + '/' + loc + '/' + prefix + '/' + slug) : '';
        out.dispatchEvent(new Event('input', { bubbles: true }));
    }

    var ctEl = document.getElementById('content_type');
    var slugEl = document.getElementById('slug');
    var locEl = document.getElementById('locale');
    if (ctEl) {
        ctEl.addEventListener('change', syncArticleCanonical);
    }
    if (slugEl) {
        slugEl.addEventListener('input', syncArticleCanonical);
    }
    if (!fixedLocale && locEl) {
        locEl.addEventListener('change', syncArticleCanonical);
    }
    syncArticleCanonical();
})();
</script>
