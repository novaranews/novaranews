<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_article_generations', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_article_generations', 'news_source_id')) {
                $table->foreignId('news_source_id')->nullable()->after('id')->constrained('news_sources')->nullOnDelete();
            }
            if (! Schema::hasColumn('ai_article_generations', 'source_guid')) {
                $table->string('source_guid', 512)->nullable()->after('source_url')->index();
            }
            if (! Schema::hasColumn('ai_article_generations', 'source_locale')) {
                $table->string('source_locale', 10)->nullable()->after('source_guid');
            }
            if (! Schema::hasColumn('ai_article_generations', 'image_url')) {
                $table->string('image_url', 2048)->nullable()->after('raw_response');
            }
            if (! Schema::hasColumn('ai_article_generations', 'image_alt')) {
                $table->string('image_alt', 255)->nullable()->after('image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_article_generations', function (Blueprint $table) {
            foreach (['news_source_id', 'source_guid', 'source_locale', 'image_url', 'image_alt'] as $col) {
                if (Schema::hasColumn('ai_article_generations', $col)) {
                    if ($col === 'news_source_id') {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
