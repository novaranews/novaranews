<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Swap feeds that often block datacenter IPs / TLS for different domains serving similar topics.
 * (Heise→Golem, NTV/Chip→AA & Log, Space.com→Phys.org, InsideEVs→Electrek, etc.)
 *
 * Safe to run after earlier URL-fix migrations; only updates rows that still match old URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            // TR — DonanımHaber / Chip / NTV / Webtekno / Hürriyet (same paths as admin; different hosts)
            'https://www.donanimhaber.com/rss/tum/' => 'https://www.donanimarsivi.com/feed/',
            'https://www.chip.com.tr/rss/' => 'https://www.log.com.tr/rss',
            'https://www.ntv.com.tr/teknoloji.rss' => 'https://www.aa.com.tr/tr/rss/default?cat=teknoloji',
            'https://www.webtekno.com/rss/uzay' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://www.hurriyet.com.tr/rss/bilim' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            // DE — avoid heise.de if IP-blocked (t3n.de: separate operator / CDN)
            'https://www.heise.de/rss/heise-atom.xml' => 'https://t3n.de/rss.xml',
            'https://www.heise.de/newsticker/heise-atom.xml' => 'https://t3n.de/rss.xml',
            // ES — official Xataka XML (not Feedburner / weblogssl)
            'https://feeds.weblogssl.com/xataka2' => 'https://www.xataka.com/index.xml',
            // FR
            'https://www.journaldugeek.com/feed/' => 'https://www.journaldunet.com/rss/',
            'https://www.caradisiac.com/rss/' => 'https://www.automobile-propre.com/feed/',
            'https://www.caradisiac.com/rss/news/' => 'https://www.automobile-propre.com/feed/',
            // EN space — SpaceNews TLS; Space.com often blocked — different CDNs
            'https://spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.space.com/feeds/all' => 'https://phys.org/rss-feed/space-news/',
            // EN EV — Verge transportation (distinct from CNET row; avoids insideevs.com blocks)
            'https://insideevs.com/rss/articles/all/' => 'https://www.theverge.com/rss/transportation/index.xml',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }

        // Numerama homepage feed used for “bilim” rows — use tag feed (not Gaming / IA rows).
        DB::table('news_sources')
            ->where('url', 'https://www.numerama.com/feed/')
            ->where('locale', 'fr')
            ->where('name', 'not like', '%Gaming%')
            ->where('name', 'not like', '%Jeux%')
            ->where('name', 'not like', '%IA%')
            ->where('name', 'not like', '%intelligence%')
            ->where('name', 'not like', '%Intelligence%')
            ->update(['url' => 'https://www.numerama.com/tag/science/feed/']);

        // MIT still on generic /feed/ — topic by category (bilim/science vs tech vs AI)
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
            ->update(['url' => 'https://www.technologyreview.com/topic/science/feed/']);
    }

    public function down(): void
    {
        // Data-only fix
    }
};
