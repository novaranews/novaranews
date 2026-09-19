<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SpaceNews TLS EOF on many servers; Webtekno uzay 404; InsideEVs path; DNS; Caradisiac 410.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            // If still on apex SpaceNews, point to NSF (www SpaceNews also TLS-fails on some hosts)
            'https://spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.webtekno.com/uzay-haberleri-k1186.xml' => 'https://www.hurriyet.com.tr/rss/bilim',
            'https://insideevs.com/feed/all/' => 'https://www.insideevs.com/feed/',
            'https://www.otomotivdunyasi.com.tr/feed/' => 'https://www.haberturk.com/rss/kategori/otomobil.xml',
            'https://www.caradisiac.com/rss/electricite/' => 'https://www.bfmtv.com/rss/auto/',
        ];

        foreach ($replace as $from => $to) {
            DB::table('news_sources')->where('url', $from)->update(['url' => $to]);
        }
    }

    public function down(): void
    {
        // Data-only fix
    }
};
