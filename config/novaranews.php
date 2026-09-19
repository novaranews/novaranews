<?php

$localeString = env('NOVARANEWS_LOCALES', 'en,tr,de,fr,es');
$parsedLocales = array_values(array_filter(array_map('trim', explode(',', (string) $localeString))));

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies (X-Forwarded-*). * = typical behind Cloudflare.
    |--------------------------------------------------------------------------
    */
    'trusted_proxies' => env('TRUSTED_PROXIES', '*'),

    /*
    |--------------------------------------------------------------------------
    | Aktif site dilleri (URL: /{locale}/...)
    | .env: NOVARANEWS_LOCALES=en,tr,de,fr,es
    |--------------------------------------------------------------------------
    */
    'locales' => $parsedLocales !== [] ? $parsedLocales : ['en', 'tr'],

    'default_locale' => env('NOVARANEWS_DEFAULT_LOCALE', 'en'),

    // Public source link used to satisfy the AGPL network-source notice.
    'source_code_url' => env('NOVARANEWS_SOURCE_CODE_URL', 'https://github.com/novaranews/novaranews'),

    /*
    |--------------------------------------------------------------------------
    | Public uploads (storage/app/public/{subdir} → URL /storage/{subdir}/…)
    |--------------------------------------------------------------------------
    */
    'public_upload_subdir' => 'media',

    /*
    |--------------------------------------------------------------------------
    | Public site cache (seconds; file/redis driver)
    |--------------------------------------------------------------------------
    */
    'cache_ttl_home' => 120,

    'cache_ttl_nav' => 1800,

    /*
    |--------------------------------------------------------------------------
    | SEO: optional publisher logo (absolute URL) for JSON-LD
    |--------------------------------------------------------------------------
    */
    'publisher_logo_url' => env('APP_URL') ? rtrim(env('APP_URL'), '/').'/logo-publisher.png' : null,

    /*
    |--------------------------------------------------------------------------
    | SEO: default og:image for pages without a featured image (absolute URL)
    |--------------------------------------------------------------------------
    */
    'default_og_image_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4 (gtag.js)
    |--------------------------------------------------------------------------
    */
    'ga4_measurement_id' => '',

    'ga4_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Search Console — HTML tag verification (content only, no "meta name=" prefix)
    |--------------------------------------------------------------------------
    */
    'google_site_verification' => '',

    /*
    |--------------------------------------------------------------------------
    | Google AdSense — auto ads / script client id (ca-pub-xxxxxxxx)
    |--------------------------------------------------------------------------
    */
    'adsense_enabled' => false,

    'adsense_client_id' => '',

    /*
    |--------------------------------------------------------------------------
    | Google News sitemap (publication name in XML)
    |--------------------------------------------------------------------------
    */
    'google_news_publication_name' => config('app.name', 'Novara News'),

    /*
    |--------------------------------------------------------------------------
    | Footer — optional social profile URLs (absolute https)
    |--------------------------------------------------------------------------
    */
    'social_links' => [],

    /*
    |--------------------------------------------------------------------------
    | Contact form (e-posta alıcısı; boşsa Mail gönderilmez, yine de form çalışır)
    |--------------------------------------------------------------------------
    */
    'contact_mail_to' => null,

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v3
    |--------------------------------------------------------------------------
    */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
        'score_threshold' => (float) env('RECAPTCHA_SCORE_THRESHOLD', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Makale URL’sinin ilk segmenti: /{locale}/{segment}/{article-slug}
    | İçerik türüne göre farklı segment (haber / rehber / analiz / inceleme …).
    | Her dil için çeviri; kategori ve diğer tür segmentleriyle çakışmaması gerekir.
    |--------------------------------------------------------------------------
    */
    'article_path_segment_by_content_type' => [
        'news' => [
            'en' => 'news',
            'tr' => 'haber',
            'de' => 'artikel',
            'fr' => 'article',
            'es' => 'noticia',
        ],
        'guide' => [
            'en' => 'guide',
            'tr' => 'rehber',
            'de' => 'ratgeber',
            'fr' => 'guide',
            'es' => 'guia',
        ],
        'analysis' => [
            'en' => 'analysis',
            'tr' => 'analiz',
            'de' => 'analyse',
            'fr' => 'analyse',
            'es' => 'analisis',
        ],
        'review' => [
            'en' => 'review',
            'tr' => 'inceleme',
            'de' => 'test',
            'fr' => 'critique',
            'es' => 'critica',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kategori listesi URL’sinin ilk segmenti: /{locale}/{segment}/{category-slug}
    | Makale ve kategori slug’larıyla çakışmaması gerekir (makale URL segmentleriyle farklı olmalı).
    |--------------------------------------------------------------------------
    */
    'category_path_segment' => [
        'en' => 'category',
        'tr' => 'kategori',
        'de' => 'kategorie',
        'fr' => 'categorie',
        'es' => 'categoria',
    ],

];
