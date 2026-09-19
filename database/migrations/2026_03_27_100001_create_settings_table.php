<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string|boolean|integer|json|secret
            $table->string('group', 50)->default('general');
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        $defaults = [
            // Bot settings
            ['key' => 'bot_enabled',         'value' => '1',                    'type' => 'boolean', 'group' => 'bot',     'label' => 'Bot Etkin',                'description' => 'AI haber botunu etkinleştir/devre dışı bırak'],
            ['key' => 'bot_model',           'value' => 'claude-sonnet-4-6',    'type' => 'string',  'group' => 'bot',     'label' => 'Claude Modeli',            'description' => 'Haber üretiminde kullanılacak Claude modeli'],
            ['key' => 'bot_max_tokens',      'value' => '4096',                 'type' => 'integer', 'group' => 'bot',     'label' => 'Maks Token',               'description' => 'Her makale için maksimum token sayısı'],
            ['key' => 'bot_articles_per_run','value' => '20',                   'type' => 'integer', 'group' => 'bot',     'label' => 'Çalıştırma Başına Makale', 'description' => 'Her scheduler çalışmasında üretilecek maksimum makale sayısı'],
            ['key' => 'bot_mock_mode',       'value' => '0',                    'type' => 'boolean', 'group' => 'bot',     'label' => 'Mock Modu',                'description' => 'Gerçek API çağrısı yerine sahte yanıt kullan (test için)'],
            ['key' => 'bot_auto_publish',    'value' => '0',                    'type' => 'boolean', 'group' => 'bot',     'label' => 'Otomatik Yayınla',         'description' => 'Üretilen haberleri editör onayı olmadan doğrudan yayınla'],
            // Site settings
            ['key' => 'site_name',           'value' => 'Novara News',          'type' => 'string',  'group' => 'site',    'label' => 'Site Adı',                 'description' => 'Sitenin adı'],
            ['key' => 'site_tagline',        'value' => 'Global News',          'type' => 'string',  'group' => 'site',    'label' => 'Site Sloganı',             'description' => 'Kısa açıklama / slogan'],
            ['key' => 'articles_per_page',   'value' => '12',                   'type' => 'integer', 'group' => 'site',    'label' => 'Sayfa Başına Makale',      'description' => 'Liste sayfalarında gösterilecek makale sayısı'],
        ];

        foreach ($defaults as $s) {
            \DB::table('settings')->insertOrIgnore($s + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
