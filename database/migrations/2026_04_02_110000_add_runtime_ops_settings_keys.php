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
            ['key' => 'ga4_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'site', 'label' => 'GA4 Enabled', 'description' => 'Enable or disable GA4 script output'],
            ['key' => 'ga4_measurement_id', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'GA4 Measurement ID', 'description' => 'Google Analytics 4 measurement id (G-XXXXXX)'],
            ['key' => 'google_site_verification', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'Google Site Verification', 'description' => 'Google Search Console verification token'],
            ['key' => 'adsense_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'site', 'label' => 'AdSense Enabled', 'description' => 'Enable AdSense script output'],
            ['key' => 'adsense_client_id', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'AdSense Client ID', 'description' => 'AdSense client id (ca-pub-xxxxxxxx)'],
            ['key' => 'contact_mail_to', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'Contact Mail To', 'description' => 'Contact form destination email address'],
            ['key' => 'site_cache_ttl_home', 'value' => '120', 'type' => 'integer', 'group' => 'site', 'label' => 'Home Cache TTL', 'description' => 'Home page cache TTL (seconds)'],
            ['key' => 'site_cache_ttl_nav', 'value' => '1800', 'type' => 'integer', 'group' => 'site', 'label' => 'Nav Cache TTL', 'description' => 'Navigation cache TTL (seconds)'],
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
            'ga4_enabled',
            'ga4_measurement_id',
            'google_site_verification',
            'adsense_enabled',
            'adsense_client_id',
            'contact_mail_to',
            'site_cache_ttl_home',
            'site_cache_ttl_nav',
        ])->delete();
    }
};
