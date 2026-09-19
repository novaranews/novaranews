{{-- ── Google Reader Revenue Manager (Subscribe with Google Basic) ──────────
     Her dil için ayrı Publisher Center publication ID'si kullanılır.
     lang + isPartOfProductId locale'e göre otomatik seçilir.
─────────────────────────────────────────────────────────────────────────── --}}
@php
$swgIds = [
    'en' => 'CAow5oXLDA:openaccess',
    'tr' => 'CAow54XLDA:openaccess',
    'de' => 'CAow-YXLDA:openaccess',
    'fr' => 'CAowiIbLDA:openaccess',
    'es' => 'CAowh4bLDA:openaccess',
];
$locale   = app()->getLocale();
$swgId    = $swgIds[$locale] ?? $swgIds['en'];
@endphp
<script async type="application/javascript"
    src="https://news.google.com/swg/js/v1/swg-basic.js"></script>
<script>
(self.SWG_BASIC = self.SWG_BASIC || []).push(basicSubscriptions => {
    basicSubscriptions.init({
        type: "NewsArticle",
        isPartOfType: ["Product"],
        isPartOfProductId: "{{ $swgId }}",
        clientOptions: { theme: "light", lang: "{{ $locale }}" },
    });
});
</script>
