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
            DB::table('settings')->updateOrInsert(
                ['key' => 'search_meta_template_'.$locale],
                [
                    'value' => (string) trans('site.search_meta', [], $locale),
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Search Meta Template ('.strtoupper($locale).')',
                    'description' => 'Use :query and :site placeholders.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'authors_meta_template_'.$locale],
                [
                    'value' => (string) trans('site.authors_meta', [], $locale),
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Authors Meta Template ('.strtoupper($locale).')',
                    'description' => 'Use :site placeholder.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'author_meta_template_'.$locale],
                [
                    'value' => ':name - :site',
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Author Profile Meta Template ('.strtoupper($locale).')',
                    'description' => 'Use :name, :title and :site placeholders.',
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
            $keys[] = 'search_meta_template_'.$locale;
            $keys[] = 'authors_meta_template_'.$locale;
            $keys[] = 'author_meta_template_'.$locale;
        }

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};

