<?php

use Database\Seeders\NewsSourcesSyncSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Repopulates news_sources when the table was cleared or URLs drifted from migrations.
 * Safe to run repeatedly (idempotent seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        if (! class_exists(NewsSourcesSyncSeeder::class)) {
            return;
        }

        (new NewsSourcesSyncSeeder)->run();
    }

    public function down(): void
    {
        // Data sync; no structural rollback.
    }
};
