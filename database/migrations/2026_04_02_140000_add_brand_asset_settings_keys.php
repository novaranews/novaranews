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

        $rows = [
            ['key' => 'site_mark_logo_url', 'value' => '/images/novaranews-mark.svg', 'type' => 'string', 'group' => 'site', 'label' => 'Site Mark Logo URL', 'description' => 'Header/footer brand mark image URL'],
            ['key' => 'site_favicon_svg_url', 'value' => '/favicon.svg', 'type' => 'string', 'group' => 'site', 'label' => 'Favicon SVG URL', 'description' => 'Primary favicon SVG URL'],
            ['key' => 'site_favicon_ico_url', 'value' => '/favicon.ico', 'type' => 'string', 'group' => 'site', 'label' => 'Favicon ICO URL', 'description' => 'Legacy favicon ICO URL'],
            ['key' => 'site_apple_touch_icon_url', 'value' => '/images/apple-touch-icon.svg', 'type' => 'string', 'group' => 'site', 'label' => 'Apple Touch Icon URL', 'description' => 'Apple touch icon URL (SVG or PNG)'],
            ['key' => 'site_apple_touch_icon_png_url', 'value' => '/apple-touch-icon.png', 'type' => 'string', 'group' => 'site', 'label' => 'Apple Touch Icon PNG URL', 'description' => 'Apple touch icon PNG URL (180x180)'],
        ];

        foreach ($rows as $row) {
            if (! DB::table('settings')->where('key', $row['key'])->exists()) {
                DB::table('settings')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', [
            'site_mark_logo_url',
            'site_favicon_svg_url',
            'site_favicon_ico_url',
            'site_apple_touch_icon_url',
            'site_apple_touch_icon_png_url',
        ])->delete();
    }
};

