<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            'https://www.aa.com.tr/tr/rss/default?cat=teknoloji' => 'https://www.ntv.com.tr/teknoloji.rss',
            'https://www.sabah.com.tr/rss/bilim.xml' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://www.heise.de/mobil/rss/heise-mobil-atom.xml' => 'https://rss.golem.de/rss.php?r=mo-ms&feed=RSS2.0',
            'https://www.igen.fr/rss.xml' => 'https://www.frandroid.com/feed',
            'https://www.androidmag.fr/feed/' => 'https://www.frandroid.com/feed',
            'https://www.xatakandroid.com/rss/' => 'https://www.xatakandroid.com/index.xml',
            'https://www.theverge.com/rss/gaming/index.xml' => 'https://feeds.ign.com/ign/games-all',
            'https://www.oyungezer.com.tr/feed/' => 'https://www.tamindir.com/feed/',
            'https://feeds.feedburner.com/gamepro' => 'https://www.pcgames.de/feed/',
            'https://www.gameblog.fr/rss' => 'https://www.jeuxvideo.com/rss/rss-news.xml',
            'https://www.anandtech.com/rss/news' => 'https://www.techspot.com/backend.xml',
            'https://www.extremetech.com/feed' => 'https://www.techradar.com/rss',
            'https://www.hardware.fr/rss.xml' => 'https://www.clubic.com/feed/news.rss',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }

        // technopat.net/feed is used in both mobile + hardware rows
        DB::table('news_sources')
            ->where('url', 'https://www.technopat.net/feed/')
            ->where('locale', 'tr')
            ->where('category_key', 'mobile')
            ->update(['url' => 'https://www.log.com.tr/rss']);

        DB::table('news_sources')
            ->where('url', 'https://www.technopat.net/feed/')
            ->where('locale', 'tr')
            ->where('category_key', 'hardware')
            ->update(['url' => 'https://www.hurriyet.com.tr/rss/teknoloji']);
    }

    public function down(): void
    {
        // data-only corrective migration
    }
};
