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
            // Bot settings
            ['key' => 'bot_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'bot', 'label' => 'Bot Enabled', 'description' => 'Enable or disable AI bot'],
            ['key' => 'bot_model', 'value' => 'claude-sonnet-4-6', 'type' => 'string', 'group' => 'bot', 'label' => 'Bot Model', 'description' => 'Model used for AI generation'],
            ['key' => 'bot_max_tokens', 'value' => '4096', 'type' => 'integer', 'group' => 'bot', 'label' => 'Bot Max Tokens', 'description' => 'Max output tokens'],
            ['key' => 'bot_articles_per_run', 'value' => '20', 'type' => 'integer', 'group' => 'bot', 'label' => 'Bot Articles Per Run', 'description' => 'Max items per scheduler run'],
            ['key' => 'bot_mock_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'bot', 'label' => 'Bot Mock Mode', 'description' => 'Use mock generation mode'],
            ['key' => 'bot_auto_publish', 'value' => '0', 'type' => 'boolean', 'group' => 'bot', 'label' => 'Bot Auto Publish', 'description' => 'Publish generated items automatically'],

            // Site settings (critical for admin settings page persistence)
            ['key' => 'site_name', 'value' => 'Novara News', 'type' => 'string', 'group' => 'site', 'label' => 'Site Name', 'description' => 'Primary site name'],
            ['key' => 'site_tagline', 'value' => 'Global News', 'type' => 'string', 'group' => 'site', 'label' => 'Site Tagline', 'description' => 'Short site tagline'],
            ['key' => 'articles_per_page', 'value' => '12', 'type' => 'integer', 'group' => 'site', 'label' => 'Articles Per Page', 'description' => 'Pagination size for listing pages'],
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
            'bot_enabled',
            'bot_model',
            'bot_max_tokens',
            'bot_articles_per_run',
            'bot_mock_mode',
            'bot_auto_publish',
            'site_name',
            'site_tagline',
            'articles_per_page',
        ])->delete();
    }
};

