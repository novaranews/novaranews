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
            $titleKey = 'default_meta_title_'.$locale;
            $descKey = 'default_meta_description_'.$locale;

            DB::table('settings')->updateOrInsert(
                ['key' => $titleKey],
                [
                    'value' => (string) trans('site.home_title', [], $locale),
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Default Meta Title ('.strtoupper($locale).')',
                    'description' => 'Homepage fallback title for locale '.$locale,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => $descKey],
                [
                    'value' => (string) trans('site.meta_description_default', [], $locale),
                    'type' => 'string',
                    'group' => 'site',
                    'label' => 'Default Meta Description ('.strtoupper($locale).')',
                    'description' => 'Homepage fallback meta description for locale '.$locale,
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
            $keys[] = 'default_meta_title_'.$locale;
            $keys[] = 'default_meta_description_'.$locale;
        }

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};

