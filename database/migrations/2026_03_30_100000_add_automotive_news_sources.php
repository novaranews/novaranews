<?php

use App\Models\NewsSource;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds tech-focused automotive RSS sources (EV, autonomous driving, connected vehicles).
 * analysis and guide categories intentionally have no RSS sources — they are editorially generated.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            // ── ENGLISH ──────────────────────────────────────────────────────────
            ['name' => 'Electrek',                'url' => 'https://electrek.co/feed/',                                        'locale' => 'en', 'category_key' => 'automotive'],
            ['name' => 'The Verge Transportation', 'url' => 'https://www.theverge.com/rss/transportation/index.xml',            'locale' => 'en', 'category_key' => 'automotive'],
            ['name' => 'InsideEVs',               'url' => 'https://insideevs.com/feed/all/',                                  'locale' => 'en', 'category_key' => 'automotive'],
            ['name' => 'Ars Technica Cars',        'url' => 'https://feeds.arstechnica.com/arstechnica/cars',                   'locale' => 'en', 'category_key' => 'automotive'],

            // ── TURKISH ───────────────────────────────────────────────────────────
            ['name' => 'Otomotiv Dünyası',         'url' => 'https://www.otomotivdunyasi.com.tr/feed/',                        'locale' => 'tr', 'category_key' => 'automotive'],
            ['name' => 'Shiftdelete Otomotiv',     'url' => 'https://shiftdelete.net/feed',                                    'locale' => 'tr', 'category_key' => 'automotive'],
            ['name' => 'Donanımhaber Otomotiv',    'url' => 'https://www.donanimhaber.com/rss/',                               'locale' => 'tr', 'category_key' => 'automotive'],

            // ── GERMAN ────────────────────────────────────────────────────────────
            ['name' => 'Heise Autos',              'url' => 'https://www.heise.de/autos/rss/news-atom.xml',                    'locale' => 'de', 'category_key' => 'automotive'],
            ['name' => 'Elektroauto-News',         'url' => 'https://www.elektroauto-news.net/feed',                           'locale' => 'de', 'category_key' => 'automotive'],
            ['name' => 'Golem E-Auto',             'url' => 'https://rss.golem.de/rss.php?r=mo&feed=RSS2.0',                  'locale' => 'de', 'category_key' => 'automotive'],

            // ── FRENCH ────────────────────────────────────────────────────────────
            ['name' => 'Automobile Propre',        'url' => 'https://www.automobile-propre.com/feed/',                         'locale' => 'fr', 'category_key' => 'automotive'],
            ['name' => 'Caradisiac Electrique',    'url' => 'https://www.caradisiac.com/rss/electricite/',                     'locale' => 'fr', 'category_key' => 'automotive'],

            // ── SPANISH ───────────────────────────────────────────────────────────
            ['name' => 'Motorpasión Eléctrico',    'url' => 'https://www.motorpasion.es/coches-hibridos-electricos/feedburner.xml', 'locale' => 'es', 'category_key' => 'automotive'],
            ['name' => 'Xataka Movilidad',         'url' => 'https://www.xataka.com/movilidad/feedburner.xml',                 'locale' => 'es', 'category_key' => 'automotive'],
        ];

        foreach ($sources as $source) {
            NewsSource::query()->firstOrCreate(
                ['url' => $source['url']],
                array_merge($source, ['is_active' => true])
            );
        }
    }

    public function down(): void
    {
        $urls = [
            'https://electrek.co/feed/',
            'https://www.theverge.com/rss/transportation/index.xml',
            'https://insideevs.com/feed/all/',
            'https://feeds.arstechnica.com/arstechnica/cars',
            'https://www.otomotivdunyasi.com.tr/feed/',
            'https://shiftdelete.net/feed',
            'https://www.donanimhaber.com/rss/',
            'https://www.heise.de/autos/rss/news-atom.xml',
            'https://www.elektroauto-news.net/feed',
            'https://rss.golem.de/rss.php?r=mo&feed=RSS2.0',
            'https://www.automobile-propre.com/feed/',
            'https://www.caradisiac.com/rss/electricite/',
            'https://www.motorpasion.es/coches-hibridos-electricos/feedburner.xml',
            'https://www.xataka.com/movilidad/feedburner.xml',
        ];

        NewsSource::query()->whereIn('url', $urls)->delete();
    }
};
