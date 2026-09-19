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
                'key' => 'footer_whatsapp',
                'value' => '',
                'type' => 'string',
                'group' => 'site',
                'label' => 'Footer WhatsApp number',
                'description' => 'International format for wa.me link (e.g. +44 1234 567890)',
            ],
            [
                'key' => 'footer_address',
                'value' => '',
                'type' => 'string',
                'group' => 'site',
                'label' => 'Footer postal address',
                'description' => 'Shown below copyright on the public site',
            ],
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

        DB::table('settings')->whereIn('key', ['footer_whatsapp', 'footer_address'])->delete();
    }
};
