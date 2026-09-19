<?php

use App\Models\NewsSource;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds RSS sources for remaining tech-focused categories:
 * cybersecurity, startups, science, gadgets, software, space
 */
return new class extends Migration
{
    public function up(): void
    {
        $sources = [
            // ── CYBERSECURITY ─────────────────────────────────────────────────────
            ['name' => 'The Hacker News',           'url' => 'https://feeds.feedburner.com/TheHackersNews',               'locale' => 'en', 'category_key' => 'cybersecurity'],
            ['name' => 'Krebs on Security',         'url' => 'https://krebsonsecurity.com/feed/',                         'locale' => 'en', 'category_key' => 'cybersecurity'],
            ['name' => 'Bleeping Computer',         'url' => 'https://www.bleepingcomputer.com/feed/',                    'locale' => 'en', 'category_key' => 'cybersecurity'],

            ['name' => 'BThaber Güvenlik',          'url' => 'https://www.bthaber.com/feed/',                             'locale' => 'tr', 'category_key' => 'cybersecurity'],
            ['name' => 'Webrazzi Güvenlik',         'url' => 'https://webrazzi.com/feed/',                                'locale' => 'tr', 'category_key' => 'cybersecurity'],

            ['name' => 'Heise Security',            'url' => 'https://www.heise.de/security/rss/news-atom.xml',           'locale' => 'de', 'category_key' => 'cybersecurity'],
            ['name' => 'Golem Security',            'url' => 'https://rss.golem.de/rss.php?r=sec&feed=RSS2.0',           'locale' => 'de', 'category_key' => 'cybersecurity'],

            ['name' => 'ZATAZ',                     'url' => 'https://www.zataz.com/feed/',                               'locale' => 'fr', 'category_key' => 'cybersecurity'],
            ['name' => 'Le Monde Pixels',           'url' => 'https://www.lemonde.fr/pixels/rss_full.xml',               'locale' => 'fr', 'category_key' => 'cybersecurity'],

            ['name' => 'Genbeta Seguridad',         'url' => 'https://www.genbeta.com/feedburner.xml',                   'locale' => 'es', 'category_key' => 'cybersecurity'],
            ['name' => 'Hipertextual Seguridad',    'url' => 'https://hipertextual.com/tag/seguridad/feed',               'locale' => 'es', 'category_key' => 'cybersecurity'],

            // ── STARTUPS ──────────────────────────────────────────────────────────
            ['name' => 'TechCrunch Startups',       'url' => 'https://techcrunch.com/category/startups/feed/',            'locale' => 'en', 'category_key' => 'startups'],
            ['name' => 'Crunchbase News',           'url' => 'https://news.crunchbase.com/feed/',                         'locale' => 'en', 'category_key' => 'startups'],

            ['name' => 'Webrazzi',                  'url' => 'https://webrazzi.com/feed/',                                'locale' => 'tr', 'category_key' => 'startups'],

            ['name' => 'Gründerszene',              'url' => 'https://www.gruenderszene.de/feed',                         'locale' => 'de', 'category_key' => 'startups'],

            ['name' => 'Maddyness',                 'url' => 'https://www.maddyness.com/feed/',                           'locale' => 'fr', 'category_key' => 'startups'],
            ['name' => 'Frenchweb',                 'url' => 'https://www.frenchweb.fr/feed',                             'locale' => 'fr', 'category_key' => 'startups'],

            ['name' => 'Xataka Empresas',           'url' => 'https://www.xataka.com/empresas-y-economia/feedburner.xml', 'locale' => 'es', 'category_key' => 'startups'],

            // ── SCIENCE ───────────────────────────────────────────────────────────
            ['name' => 'New Scientist',             'url' => 'https://www.newscientist.com/feed/home/',                   'locale' => 'en', 'category_key' => 'science'],
            ['name' => 'Science Daily',             'url' => 'https://www.sciencedaily.com/rss/all.xml',                  'locale' => 'en', 'category_key' => 'science'],
            ['name' => 'NPR Science',               'url' => 'https://feeds.npr.org/1007/rss.xml',                        'locale' => 'en', 'category_key' => 'science'],

            ['name' => 'NTV Bilim Teknoloji',       'url' => 'https://www.ntv.com.tr/bilim-teknoloji.rss',                'locale' => 'tr', 'category_key' => 'science'],
            ['name' => 'Sabah Bilim',               'url' => 'https://www.sabah.com.tr/rss/bilim.xml',                    'locale' => 'tr', 'category_key' => 'science'],

            ['name' => 'Spiegel Wissenschaft',      'url' => 'https://www.spiegel.de/wissenschaft/index.rss',             'locale' => 'de', 'category_key' => 'science'],
            ['name' => 'DW Wissenschaft',           'url' => 'https://rss.dw.com/rdf/rss-de-wissenschaft',                'locale' => 'de', 'category_key' => 'science'],

            ['name' => 'Le Monde Sciences',         'url' => 'https://www.lemonde.fr/sciences/rss_full.xml',              'locale' => 'fr', 'category_key' => 'science'],
            ['name' => 'Sciences et Avenir',        'url' => 'https://www.sciencesetavenir.fr/rss.xml',                   'locale' => 'fr', 'category_key' => 'science'],

            ['name' => 'El Pais Ciencia',           'url' => 'https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/ciencia/portada', 'locale' => 'es', 'category_key' => 'science'],
            ['name' => 'Muy Interesante',           'url' => 'https://www.muyinteresante.es/rss',                         'locale' => 'es', 'category_key' => 'science'],

            // ── GADGETS & HARDWARE ────────────────────────────────────────────────
            ['name' => 'Engadget',                  'url' => 'https://www.engadget.com/rss.xml',                          'locale' => 'en', 'category_key' => 'gadgets'],
            ['name' => 'CNET',                      'url' => 'https://www.cnet.com/rss/all/',                             'locale' => 'en', 'category_key' => 'gadgets'],
            ['name' => 'The Verge Gadgets',         'url' => 'https://www.theverge.com/rss/gadgets/index.xml',            'locale' => 'en', 'category_key' => 'gadgets'],

            ['name' => 'Donanimhaber',              'url' => 'https://www.donanimhaber.com/rss/',                         'locale' => 'tr', 'category_key' => 'gadgets'],
            ['name' => 'Chip TR Donanim',           'url' => 'https://www.chip.com.tr/rss/tum-haberler.xml',             'locale' => 'tr', 'category_key' => 'gadgets'],

            ['name' => 'Golem Hardware',            'url' => 'https://rss.golem.de/rss.php?r=hw&feed=RSS2.0',            'locale' => 'de', 'category_key' => 'gadgets'],
            ['name' => 'Heise Gadgets',             'url' => 'https://www.heise.de/rss/heise-atom.xml',                  'locale' => 'de', 'category_key' => 'gadgets'],

            ['name' => 'Les Numeriques',            'url' => 'https://www.lesnumeriques.com/rss/',                        'locale' => 'fr', 'category_key' => 'gadgets'],

            ['name' => 'Xataka Moviles',            'url' => 'https://www.xatakamovil.com/feedburner.xml',               'locale' => 'es', 'category_key' => 'gadgets'],

            // ── SOFTWARE & DEV ────────────────────────────────────────────────────
            ['name' => 'Hacker News',               'url' => 'https://hnrss.org/frontpage',                               'locale' => 'en', 'category_key' => 'software'],
            ['name' => 'Dev.to',                    'url' => 'https://dev.to/feed',                                       'locale' => 'en', 'category_key' => 'software'],
            ['name' => 'GitHub Blog',               'url' => 'https://github.blog/feed/',                                 'locale' => 'en', 'category_key' => 'software'],

            ['name' => 'Webrazzi Yazilim',          'url' => 'https://webrazzi.com/feed/',                                'locale' => 'tr', 'category_key' => 'software'],

            ['name' => 'Heise Developer',           'url' => 'https://www.heise.de/developer/rss/news-atom.xml',          'locale' => 'de', 'category_key' => 'software'],

            ['name' => 'Le Monde Pixels Dev',       'url' => 'https://www.lemonde.fr/pixels/rss_full.xml',               'locale' => 'fr', 'category_key' => 'software'],

            ['name' => 'Genbeta',                   'url' => 'https://www.genbeta.com/feedburner.xml',                   'locale' => 'es', 'category_key' => 'software'],

            // ── SPACE & FUTURE ────────────────────────────────────────────────────
            ['name' => 'Space.com',                 'url' => 'https://www.space.com/feeds/all',                           'locale' => 'en', 'category_key' => 'space'],
            ['name' => 'NASA News',                 'url' => 'https://www.nasa.gov/news-release/feed/',                   'locale' => 'en', 'category_key' => 'space'],
            ['name' => 'SpaceNews',                 'url' => 'https://spacenews.com/feed/',                               'locale' => 'en', 'category_key' => 'space'],

            ['name' => 'Webtekno Uzay',             'url' => 'https://www.webtekno.com/uzay-haberleri-k1186.xml',         'locale' => 'tr', 'category_key' => 'space'],

            ['name' => 'Spiegel Weltraum',          'url' => 'https://www.spiegel.de/wissenschaft/weltall/index.rss',     'locale' => 'de', 'category_key' => 'space'],

            ['name' => 'Futura Sciences Espace',    'url' => 'https://www.futura-sciences.com/rss/actualites.xml',        'locale' => 'fr', 'category_key' => 'space'],
            ['name' => 'Le Monde Espace',           'url' => 'https://www.lemonde.fr/espace/rss_full.xml',               'locale' => 'fr', 'category_key' => 'space'],

            ['name' => 'El Pais Espacio',           'url' => 'https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/ciencia/portada', 'locale' => 'es', 'category_key' => 'space'],
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
            'https://feeds.feedburner.com/TheHackersNews',
            'https://krebsonsecurity.com/feed/',
            'https://www.bleepingcomputer.com/feed/',
            'https://www.bthaber.com/feed/',
            'https://webrazzi.com/feed/',
            'https://www.heise.de/security/rss/news-atom.xml',
            'https://rss.golem.de/rss.php?r=sec&feed=RSS2.0',
            'https://www.zataz.com/feed/',
            'https://www.lemonde.fr/pixels/rss_full.xml',
            'https://www.genbeta.com/feedburner.xml',
            'https://hipertextual.com/tag/seguridad/feed',
            'https://techcrunch.com/category/startups/feed/',
            'https://news.crunchbase.com/feed/',
            'https://www.gruenderszene.de/feed',
            'https://www.maddyness.com/feed/',
            'https://www.frenchweb.fr/feed',
            'https://www.xataka.com/empresas-y-economia/feedburner.xml',
            'https://www.newscientist.com/feed/home/',
            'https://www.sciencedaily.com/rss/all.xml',
            'https://feeds.npr.org/1007/rss.xml',
            'https://www.ntv.com.tr/bilim-teknoloji.rss',
            'https://www.sabah.com.tr/rss/bilim.xml',
            'https://www.spiegel.de/wissenschaft/index.rss',
            'https://rss.dw.com/rdf/rss-de-wissenschaft',
            'https://www.lemonde.fr/sciences/rss_full.xml',
            'https://www.sciencesetavenir.fr/rss.xml',
            'https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/ciencia/portada',
            'https://www.muyinteresante.es/rss',
            'https://www.engadget.com/rss.xml',
            'https://www.cnet.com/rss/all/',
            'https://www.theverge.com/rss/gadgets/index.xml',
            'https://www.donanimhaber.com/rss/',
            'https://rss.golem.de/rss.php?r=hw&feed=RSS2.0',
            'https://www.lesnumeriques.com/rss/',
            'https://www.xatakamovil.com/feedburner.xml',
            'https://hnrss.org/frontpage',
            'https://dev.to/feed',
            'https://github.blog/feed/',
            'https://www.heise.de/developer/rss/news-atom.xml',
            'https://www.space.com/feeds/all',
            'https://www.nasa.gov/news-release/feed/',
            'https://spacenews.com/feed/',
            'https://www.webtekno.com/uzay-haberleri-k1186.xml',
            'https://www.spiegel.de/wissenschaft/weltall/index.rss',
            'https://www.futura-sciences.com/rss/actualites.xml',
            'https://www.lemonde.fr/espace/rss_full.xml',
        ];

        NewsSource::query()->whereIn('url', $urls)->delete();
    }
};
