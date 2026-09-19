<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes feeds that still appear as "faulty" in admin: 404/410, legacy paths, TLS hosts,
 * duplicate Numerama homepage URL, HTML Son Dakika, or non-canonical mirrors.
 *
 * Safe to run after older URL-fix migrations; only updates matching rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            'https://www.webtekno.com/rss/uzay' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://www.donanimhaber.com/rss/uzay' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://feeds.weblogssl.com/xataka2' => 'https://www.xataka.com/index.xml',
            'https://www.caradisiac.com/rss/news/' => 'https://www.bfmtv.com/rss/auto/',
            'https://insideevs.com/rss/articles/all/' => 'https://www.insideevs.com/feed/',
            'https://www.hurriyet.com.tr/rss/bilim' => 'https://www.aa.com.tr/tr/rss/default?cat=bilim-teknoloji',
            'https://gizmodo.com/rss' => 'https://gizmodo.com/feed',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }

        // MIT: generic /feed/ still on DB for some rows — map by category.
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

        // FR technology: distinct from homepage / gaming / IA rows that also used /feed/.
        DB::table('news_sources')
            ->where('url', 'https://www.numerama.com/feed/')
            ->where('locale', 'fr')
            ->where('category_key', 'technology')
            ->update(['url' => 'https://www.numerama.com/tag/tech/feed/']);
    }

    public function down(): void
    {
        // Data-only fix
    }
};
