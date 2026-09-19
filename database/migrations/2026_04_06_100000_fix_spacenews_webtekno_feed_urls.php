<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SpaceNews (apex + www) TLS EOF on many PHP/cURL stacks → NASA Spaceflight RSS.
 * Webtekno uzay legacy XML path → Hürriyet bilim RSS.
 *
 * Safe if 2026_04_05 already ran: replaces remaining broken URLs only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        $replace = [
            'https://spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.spacenews.com/feed/' => 'https://www.nasaspaceflight.com/feed/',
            'https://www.webtekno.com/uzay-haberleri-k1186.xml' => 'https://www.hurriyet.com.tr/rss/bilim',
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
