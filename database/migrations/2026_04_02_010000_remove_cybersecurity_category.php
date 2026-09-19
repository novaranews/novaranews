<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the cybersecurity category.
 *
 * - Articles in this category are reassigned to 'technology'.
 * - RSS news sources linked to this category are reassigned to 'technology'.
 * - Category row and translations are deleted.
 */
return new class extends Migration
{
    private const REMOVED_KEYS = ['cybersecurity'];

    public function up(): void
    {
        $technologyId = DB::table('categories')->where('key', 'technology')->value('id');

        if (! $technologyId) {
            return;
        }

        $removedIds = DB::table('categories')
            ->whereIn('key', self::REMOVED_KEYS)
            ->pluck('id')
            ->all();

        if (empty($removedIds)) {
            return;
        }

        DB::table('articles')
            ->whereIn('category_id', $removedIds)
            ->update(['category_id' => $technologyId]);

        DB::table('news_sources')
            ->whereIn('category_key', self::REMOVED_KEYS)
            ->update(['category_key' => 'technology']);

        DB::table('category_translations')
            ->whereIn('category_id', $removedIds)
            ->delete();

        DB::table('categories')
            ->whereIn('id', $removedIds)
            ->delete();
    }

    public function down(): void
    {
        // Intentionally not reversible. Recreate category in admin if needed.
    }
};
