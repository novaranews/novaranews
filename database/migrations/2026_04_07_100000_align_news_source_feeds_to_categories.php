<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hürriyet /rss/bilim returns Son Dakika HTML (not RSS). Align feeds to category-specific endpoints.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $urlOnly = [
            // Was HTML redirect to son dakika; AA cat=bilim-teknoloji items are science/tech
            'https://www.hurriyet.com.tr/rss/bilim' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://www.webtekno.com/uzay-haberleri-k1186.xml' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            // MIT: topic feed fits AI category better than homepage feed
            'https://www.technologyreview.com/feed/' => 'https://www.technologyreview.com/topic/artificial-intelligence/feed/',
            // Distinct from Numerama Gaming (same homepage feed was duplicated)
            'https://www.numerama.com/feed/' => 'https://www.numerama.com/tag/intelligence-artificielle/feed/',
        ];

        foreach ($urlOnly as $from => $to) {
            // Numerama: only update the FR artificial-intelligence row (not gaming)
            if ($from === 'https://www.numerama.com/feed/') {
                DB::table('news_sources')
                    ->where('url', $from)
                    ->where('locale', 'fr')
                    ->where('category_key', 'artificial-intelligence')
                    ->update(['url' => $to]);

                continue;
            }

            // MIT: only EN artificial-intelligence
            if ($from === 'https://www.technologyreview.com/feed/') {
                DB::table('news_sources')
                    ->where('url', $from)
                    ->where('locale', 'en')
                    ->where('category_key', 'artificial-intelligence')
                    ->where('name', 'MIT Technology Review')
                    ->update(['url' => $to]);

                continue;
            }

            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }

        DB::table('news_sources')
            ->where('name', 'Webtekno Uzay')
            ->where('locale', 'tr')
            ->update(['name' => 'AA Bilim Teknoloji']);

        // Automotive: general teknoloji RSS is wrong label; AA otomobil is category-specific
        DB::table('news_sources')
            ->where('name', 'Donanımhaber Otomotiv')
            ->where('locale', 'tr')
            ->update(['url' => 'https://www.aa.com.tr/tr/rss/default?cat=otomobil']);
    }

    public function down(): void
    {
        // Data-only fix
    }
};
