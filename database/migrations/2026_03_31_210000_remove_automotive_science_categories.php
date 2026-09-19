<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the automotive and science categories.
 *
 * - Articles in these categories are reassigned to the 'technology' category.
 * - RSS news sources linked to these categories are reassigned to 'technology'.
 * - The category rows themselves (and their translations) are deleted.
 */
return new class extends Migration
{
    private const REMOVED_KEYS = ['automotive', 'science'];

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
