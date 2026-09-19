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
            ['key' => 'social_x_visible', 'value' => '1', 'type' => 'boolean', 'group' => 'site', 'label' => 'Show X on site', 'description' => 'Footer social icon'],
            ['key' => 'social_facebook_visible', 'value' => '1', 'type' => 'boolean', 'group' => 'site', 'label' => 'Show Facebook on site', 'description' => 'Footer social icon'],
            ['key' => 'social_instagram_visible', 'value' => '1', 'type' => 'boolean', 'group' => 'site', 'label' => 'Show Instagram on site', 'description' => 'Footer social icon'],
            ['key' => 'social_youtube_visible', 'value' => '1', 'type' => 'boolean', 'group' => 'site', 'label' => 'Show YouTube on site', 'description' => 'Footer social icon'],
            ['key' => 'social_linkedin_visible', 'value' => '1', 'type' => 'boolean', 'group' => 'site', 'label' => 'Show LinkedIn on site', 'description' => 'Footer social icon'],
        ];

        foreach ($rows as $row) {
            if (! DB::table('settings')->where('key', $row['key'])->exists()) {
                DB::table('settings')->insert($row + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', [
            'social_x_visible',
            'social_facebook_visible',
            'social_instagram_visible',
            'social_youtube_visible',
            'social_linkedin_visible',
        ])->delete();
    }
};
