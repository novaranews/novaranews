<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $locales = (array) config('novaranews.locales', ['en']);

        foreach ($locales as $locale) {
            $metaKey = 'category_meta_description_template_'.$locale;
            $introKey = 'category_intro_template_'.$locale;

            DB::table('settings')->updateOrInsert(
                ['key' => $metaKey],
                [
                    'value' => (string) trans('site.category_meta_description', [], $locale),
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Category Meta Template ('.strtoupper($locale).')',
                    'description' => 'Use :category and :site placeholders.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => $introKey],
                [
                    'value' => (string) trans('site.category_intro', [], $locale),
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Category Intro Template ('.strtoupper($locale).')',
                    'description' => 'Use :category placeholder.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $locales = (array) config('novaranews.locales', ['en']);
        $keys = [];
        foreach ($locales as $locale) {
            $keys[] = 'category_meta_description_template_'.$locale;
            $keys[] = 'category_intro_template_'.$locale;
        }

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};

