<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces admin-panel URLs that 404/410, return Son-Dakika HTML, or use fragile mirrors
 * (Feedburner, apex domains, legacy paths). Targets the 14 common failing rows from production.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            // TR — legacy paths return 404
            'https://www.donanimhaber.com/rss/tum-haberler' => 'https://www.donanimarsivi.com/feed/',
            'https://www.webtekno.com/rss/uzay' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://www.chip.com.tr/rss/bilim' => 'https://www.chip.com.tr/rss/',
            'https://www.ntv.com.tr/bilim.rss' => 'https://www.ntv.com.tr/teknoloji.rss',
            // EN — TLS / apex / short path
            'https://spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://insideevs.com/rss/' => 'https://www.insideevs.com/feed/',
            // DE — same Atom content, path used more reliably on some cURL stacks
            'https://www.heise.de/rss/heise-atom.xml' => 'https://www.heise.de/newsticker/heise-atom.xml',
            // ES — official site feed (weblogssl redirect / legacy)
            'https://feeds.weblogssl.com/xataka2' => 'https://www.xataka.com/index.xml',
            // FR — root RSS is gone (410)
            'https://www.caradisiac.com/rss/' => 'https://www.bfmtv.com/rss/auto/',
            'https://www.caradisiac.com/rss/news/' => 'https://www.bfmtv.com/rss/auto/',
            // EN science — canonical Ars URL (Feedburner sometimes blocked); Wired path → stable science feed
            'https://feeds.arstechnica.com/arstechnica/science' => 'https://arstechnica.com/science/feed/',
            // Wired category feed works in browsers; Nature uses RSS 1.0/rdf — our parser expects RSS2/Atom.
            'https://www.wired.com/feed/category/science/latest/rss' => 'https://www.newscientist.com/feed/home/',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }

        // Hürriyet /rss/bilim → HTML Son Dakika (not RSS)
        DB::table('news_sources')
            ->where('url', 'https://www.hurriyet.com.tr/rss/bilim')
            ->update(['url' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji']);

        // Numerama homepage feed duplicated across categories — technology row gets a tech tag feed.
        DB::table('news_sources')
            ->where('url', 'https://www.numerama.com/feed/')
            ->where('locale', 'fr')
            ->where('category_key', 'technology')
            ->update(['url' => 'https://www.numerama.com/tag/tech/feed/']);

        // MIT generic homepage Atom → topic feeds (any remaining /feed/)
        DB::table('news_sources')
            ->where('url', 'https://www.technologyreview.com/feed/')
            ->where('name', 'MIT Technology Review')
            ->where('category_key', 'artificial-intelligence')
            ->update(['url' => 'https://www.technologyreview.com/topic/artificial-intelligence/feed/']);

        DB::table('news_sources')
            ->where('url', 'https://www.technologyreview.com/feed/')
            ->where('name', 'MIT Technology Review')
            ->where('category_key', 'technology')
            ->update(['url' => 'https://www.technologyreview.com/topic/technology/feed/']);

        DB::table('news_sources')
            ->where('url', 'https://www.technologyreview.com/feed/')
            ->where('name', 'MIT Technology Review')
            ->update(['url' => 'https://www.technologyreview.com/topic/technology/feed/']);
    }

    public function down(): void
    {
        // Data-only fix
    }
};
