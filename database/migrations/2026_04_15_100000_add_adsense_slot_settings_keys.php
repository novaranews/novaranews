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
            [
                'key'         => 'adsense_slot_in_article',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'site',
                'label'       => 'AdSense In-Article Slot ID',
                'description' => 'AdSense in-article reklam birimi slot ID (makale gövdesi içi)',
            ],
            [
                'key'         => 'adsense_slot_display',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'site',
                'label'       => 'AdSense Display Slot ID',
                'description' => 'AdSense display reklam birimi slot ID (makale altı)',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(
                ['key' => $row['key']],
                $row + ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', [
            'adsense_slot_in_article',
            'adsense_slot_display',
        ])->delete();
    }
};
