<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin health check: replace endpoints that 404, return HTML, or use RDF/legacy paths
 * so RSS 2.0 parses as channel/item (see RssFetcherService::parseRssChannel).
 *
 * @see database/seeders/data/default_news_sources.php (same targets)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            'https://rss.dw.com/rdf/rss-de-wissenschaft' => 'https://rss.dw.com/xml/rss-de-wissenschaft',
            'https://www.androidpit.de/feed/rss/news' => 'https://www.nextpit.de/feed/',
            'https://www.gamestar.de/rss/news/' => 'https://feeds.feedburner.com/gamestar',
            'https://www.gamepro.de/rss/news/' => 'https://feeds.feedburner.com/gamepro',
            'https://www.pcgames.de/rss/news/' => 'https://www.pcgames.de/feed/',
            'https://www.computerbase.de/feed/news.rss' => 'https://www.computerbase.de/rss/news.xml',
            'https://www.notebookcheck.net/News.8.0.html?feed=rss' => 'https://www.notebookcheck.net/RSS-Feed-All-Articles-EN.165552.0.html',
            'https://www.hardwareluxx.de/community/backend.php/threads?prefix_id=6' => 'https://www.hardwareluxx.de/community/forums/-/index.rss',
            'https://www.3djuegos.com/rss/noticias.php' => 'https://www.eurogamer.es/feed',
            'https://vandal.elespanol.com/rss/portada.xml' => 'https://www.xataka.com/index.xml',
            'https://www.meristation.com/rss/portada/news' => 'https://objetos.estaticos-marca.com/rss/videojuegos.xml',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }
    }

    public function down(): void
    {
        // Data-only fix; reversing would reintroduce broken endpoints.
    }
};
