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
            ['key' => 'default_meta_title', 'value' => 'Novara News - AI and Technology News', 'type' => 'string', 'group' => 'site', 'label' => 'Default Meta Title', 'description' => 'Fallback title used when page-level SEO title is empty'],
            ['key' => 'default_meta_description', 'value' => 'Latest AI and technology news, analysis, and expert guides from Novara News.', 'type' => 'string', 'group' => 'site', 'label' => 'Default Meta Description', 'description' => 'Fallback description used when page-level SEO description is empty'],
            ['key' => 'default_og_image_url', 'value' => (string) config('novaranews.default_og_image_url', ''), 'type' => 'string', 'group' => 'site', 'label' => 'Default OG Image URL', 'description' => 'Fallback social sharing image URL'],
            ['key' => 'publisher_name', 'value' => (string) config('app.name', 'Novara News'), 'type' => 'string', 'group' => 'site', 'label' => 'Publisher Name', 'description' => 'Organization name used in structured data and News sitemap'],
            ['key' => 'publisher_logo_url', 'value' => (string) config('novaranews.publisher_logo_url', ''), 'type' => 'string', 'group' => 'site', 'label' => 'Publisher Logo URL', 'description' => 'Logo URL used in structured data'],
            ['key' => 'site_logo_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'Site Logo URL', 'description' => 'Optional absolute logo URL for header/footer branding'],
            ['key' => 'site_favicon_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'Favicon URL', 'description' => 'Optional absolute favicon URL'],
            ['key' => 'social_x_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'X URL', 'description' => 'Official X profile URL'],
            ['key' => 'social_facebook_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'Facebook URL', 'description' => 'Official Facebook page URL'],
            ['key' => 'social_instagram_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'Instagram URL', 'description' => 'Official Instagram profile URL'],
            ['key' => 'social_youtube_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'YouTube URL', 'description' => 'Official YouTube channel URL'],
            ['key' => 'social_linkedin_url', 'value' => '', 'type' => 'string', 'group' => 'site', 'label' => 'LinkedIn URL', 'description' => 'Official LinkedIn page URL'],
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
            'default_meta_title',
            'default_meta_description',
            'default_og_image_url',
            'publisher_name',
            'publisher_logo_url',
            'site_logo_url',
            'site_favicon_url',
            'social_x_url',
            'social_facebook_url',
            'social_instagram_url',
            'social_youtube_url',
            'social_linkedin_url',
        ])->delete();
    }
};
