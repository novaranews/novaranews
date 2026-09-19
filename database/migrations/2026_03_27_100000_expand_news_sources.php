<?php

use App\Models\NewsSource;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            // ── ARTIFICIAL INTELLIGENCE ──────────────────────────────────────────
            ['name' => 'TechCrunch AI',             'url' => 'https://techcrunch.com/category/artificial-intelligence/feed/', 'locale' => 'en', 'category_key' => 'artificial-intelligence'],
            ['name' => 'VentureBeat AI',            'url' => 'https://venturebeat.com/category/ai/feed/',                     'locale' => 'en', 'category_key' => 'artificial-intelligence'],
            ['name' => 'MIT Technology Review',     'url' => 'https://www.technologyreview.com/feed/',                        'locale' => 'en', 'category_key' => 'artificial-intelligence'],

            ['name' => 'Webtekno Yapay Zeka',       'url' => 'https://www.webtekno.com/rss.xml',                              'locale' => 'tr', 'category_key' => 'artificial-intelligence'],
            ['name' => 'Donanimhaber AI',           'url' => 'https://www.donanimhaber.com/rss/',                             'locale' => 'tr', 'category_key' => 'artificial-intelligence'],

            ['name' => 'Heise KI',                  'url' => 'https://www.heise.de/thema/kuenstliche-intelligenz?format=rss', 'locale' => 'de', 'category_key' => 'artificial-intelligence'],
            ['name' => 'Golem KI',                  'url' => 'https://rss.golem.de/rss.php?r=ai&feed=RSS2.0',                'locale' => 'de', 'category_key' => 'artificial-intelligence'],

            ['name' => 'Le Monde IA',               'url' => 'https://www.lemonde.fr/intelligence-artificielle/rss_full.xml', 'locale' => 'fr', 'category_key' => 'artificial-intelligence'],
            ['name' => 'L\'Usine Digitale',         'url' => 'https://www.usine-digitale.fr/rss/',                           'locale' => 'fr', 'category_key' => 'artificial-intelligence'],

            ['name' => 'Xataka IA',                 'url' => 'https://www.xataka.com/feedburner.xml',                        'locale' => 'es', 'category_key' => 'artificial-intelligence'],
            ['name' => 'El Pais Tecnologia',        'url' => 'https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/tecnologia/portada', 'locale' => 'es', 'category_key' => 'artificial-intelligence'],

            // ── TECHNOLOGY ───────────────────────────────────────────────────────
            ['name' => 'The Verge',                 'url' => 'https://www.theverge.com/rss/index.xml',                       'locale' => 'en', 'category_key' => 'technology'],
            ['name' => 'Ars Technica',              'url' => 'https://feeds.arstechnica.com/arstechnica/index',              'locale' => 'en', 'category_key' => 'technology'],
            ['name' => 'Wired',                     'url' => 'https://www.wired.com/feed/rss',                               'locale' => 'en', 'category_key' => 'technology'],

            ['name' => 'Chip Türkiye',              'url' => 'https://www.chip.com.tr/rss/tum-haberler.xml',                 'locale' => 'tr', 'category_key' => 'technology'],
            ['name' => 'NTV Teknoloji',             'url' => 'https://www.ntv.com.tr/teknoloji.rss',                         'locale' => 'tr', 'category_key' => 'technology'],
            ['name' => 'ShiftDelete',               'url' => 'https://shiftdelete.net/feed',                                 'locale' => 'tr', 'category_key' => 'technology'],

            ['name' => 'Heise Online',              'url' => 'https://www.heise.de/rss/heise-atom.xml',                      'locale' => 'de', 'category_key' => 'technology'],
            ['name' => 'Golem',                     'url' => 'https://rss.golem.de/rss.php?r=sw&feed=RSS2.0',               'locale' => 'de', 'category_key' => 'technology'],
            ['name' => 'Spiegel Netzwelt',          'url' => 'https://www.spiegel.de/netzwelt/index.rss',                   'locale' => 'de', 'category_key' => 'technology'],

            ['name' => 'Le Monde Techno',           'url' => 'https://www.lemonde.fr/technologies/rss_full.xml',            'locale' => 'fr', 'category_key' => 'technology'],
            ['name' => '01net',                     'url' => 'https://www.01net.com/rss/actualites/',                        'locale' => 'fr', 'category_key' => 'technology'],

            ['name' => 'Xataka',                    'url' => 'https://www.xataka.com/feedburner.xml',                       'locale' => 'es', 'category_key' => 'technology'],
            ['name' => 'Hipertextual Tecnologia',   'url' => 'https://hipertextual.com/feed',                               'locale' => 'es', 'category_key' => 'technology'],
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
            'https://techcrunch.com/category/artificial-intelligence/feed/',
            'https://venturebeat.com/category/ai/feed/',
            'https://www.technologyreview.com/feed/',
            'https://www.webtekno.com/rss.xml',
            'https://www.donanimhaber.com/rss/',
            'https://www.heise.de/thema/kuenstliche-intelligenz?format=rss',
            'https://rss.golem.de/rss.php?r=ai&feed=RSS2.0',
            'https://www.lemonde.fr/intelligence-artificielle/rss_full.xml',
            'https://www.usine-digitale.fr/rss/',
            'https://www.xataka.com/feedburner.xml',
            'https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/tecnologia/portada',
            'https://www.theverge.com/rss/index.xml',
            'https://feeds.arstechnica.com/arstechnica/index',
            'https://www.wired.com/feed/rss',
            'https://www.chip.com.tr/rss/tum-haberler.xml',
            'https://www.ntv.com.tr/teknoloji.rss',
            'https://shiftdelete.net/feed',
            'https://www.heise.de/rss/heise-atom.xml',
            'https://rss.golem.de/rss.php?r=sw&feed=RSS2.0',
            'https://www.spiegel.de/netzwelt/index.rss',
            'https://www.lemonde.fr/technologies/rss_full.xml',
            'https://www.01net.com/rss/actualites/',
            'https://hipertextual.com/feed',
        ];

        NewsSource::query()->whereIn('url', $urls)->delete();
    }
};
