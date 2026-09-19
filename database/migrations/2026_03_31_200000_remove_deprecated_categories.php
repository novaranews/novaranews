<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the startups, gadgets, space, analysis, and guide categories.
 *
 * - Articles in these categories are reassigned to the 'technology' category.
 * - RSS news sources linked to these categories are reassigned to 'technology'.
 * - The category rows themselves (and their translations) are deleted.
 */
return new class extends Migration
{
    private const REMOVED_KEYS = ['startups', 'gadgets', 'space', 'analysis', 'guide'];

    public function up(): void
    {
        $technologyId = DB::table('categories')->where('key', 'technology')->value('id');

        if (! $technologyId) {
            // Safety: if technology category doesn't exist yet (fresh install pre-seed),
            // skip — categories are managed in admin / DB.
            return;
        }

        $removedIds = DB::table('categories')
            ->whereIn('key', self::REMOVED_KEYS)
            ->pluck('id')
            ->all();

        if (empty($removedIds)) {
            return;
        }

        // Reassign articles
        DB::table('articles')
            ->whereIn('category_id', $removedIds)
            ->update(['category_id' => $technologyId]);

        // Reassign RSS news sources
        DB::table('news_sources')
            ->whereIn('category_key', self::REMOVED_KEYS)
            ->update(['category_key' => 'technology']);

        // Delete category translations first (FK constraint)
        DB::table('category_translations')
            ->whereIn('category_id', $removedIds)
            ->delete();

        // Delete the categories
        DB::table('categories')
            ->whereIn('id', $removedIds)
            ->delete();
    }

    public function down(): void
    {
        // Intentionally not reversible: re-creating deleted categories and
        // re-distributing articles would require the original data.
        // Add categories in admin if needed.
    }
};
