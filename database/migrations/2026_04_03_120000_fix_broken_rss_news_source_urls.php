<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace RSS URLs that return HTML, 4xx, anti-bot pages, or invalid XML with working feeds.
 *
 * @see production RssFetcher warnings (donanimhaber, heise KI query URL, feedburner, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            'https://www.heise.de/thema/kuenstliche-intelligenz?format=rss' => 'https://www.heise.de/ix/rss/news-atom.xml',
            // Legacy path 404; main index.xml is the supported feed.
            'https://www.xataka.com/rss/' => 'https://www.xataka.com/index.xml',
            'https://www.usine-digitale.fr/rss/' => 'https://www.numerama.com/feed/',
            'https://www.01net.com/rss/actualites/' => 'https://www.journaldunet.com/rss/',
            'https://www.ntv.com.tr/bilim-teknoloji.rss' => 'https://www.ntv.com.tr/teknoloji.rss',
            'https://www.xataka.com/feedburner.xml' => 'https://www.xataka.com/index.xml',
            'https://www.xataka.com/empresas-y-economia/feedburner.xml' => 'https://elreferente.es/feed/',
            'https://www.xataka.com/movilidad/feedburner.xml' => 'https://www.xatakaon.com/index.xml',
            'https://www.xatakamovil.com/feedburner.xml' => 'https://www.xatakamovil.com/index.xml',
            'https://www.genbeta.com/feedburner.xml' => 'https://www.genbeta.com/index.xml',
            'https://www.lesnumeriques.com/rss/' => 'https://www.lesnumeriques.com/feeds/rss.xml',
            'https://hipertextual.com/feed' => 'https://www.muycomputer.com/feed/',
            'https://hipertextual.com/tag/seguridad/feed' => 'https://www.redeszone.net/feed/',
            'https://www.motorpasion.es/coches-hibridos-electricos/feedburner.xml' => 'https://www.autonocion.com/feed/',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }

        // Donanımhaber /rss/ serves HTML, not RSS — split TR feeds by source name to avoid duplicate URLs.
        DB::update("
            UPDATE news_sources SET url = CASE name
                WHEN 'Donanimhaber AI' THEN 'https://www.webtekno.com/rss.xml'
                WHEN 'Donanimhaber' THEN 'https://www.shiftdelete.net/feed'
                WHEN 'Donanımhaber' THEN 'https://www.donanimarsivi.com/feed/'
                WHEN 'Donanımhaber Otomotiv' THEN 'https://www.haberturk.com/rss/kategori/teknoloji.xml'
                ELSE url
            END
            WHERE url = 'https://www.donanimhaber.com/rss/'
        ");

        // chip.com.tr: legacy path 404; map rows to distinct feeds.
        DB::update("
            UPDATE news_sources SET url = CASE name
                WHEN 'Chip Türkiye' THEN 'https://www.chip.com.tr/rss/'
                WHEN 'Chip TR Donanim' THEN 'https://www.log.com.tr/rss'
                ELSE url
            END
            WHERE url = 'https://www.chip.com.tr/rss/tum-haberler.xml'
        ");

        // Hardware migration used main chip RSS — keep one row on chip, one on Hürriyet to limit overlap.
        DB::table('news_sources')
            ->where('url', 'https://www.chip.com.tr/rss/')
            ->where('name', 'Chip TR Donanım')
            ->update(['url' => 'https://www.hurriyet.com.tr/rss/teknoloji']);
    }

    public function down(): void
    {
        // Data-only fix; reversing would reintroduce broken endpoints.
    }
};
