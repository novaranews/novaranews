<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ai_article_generations', 'source_packets')) {
            return;
        }

        Schema::table('ai_article_generations', function (Blueprint $table) {
            $table->json('source_packets')->nullable()->after('source_text');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ai_article_generations', 'source_packets')) {
            return;
        }

        Schema::table('ai_article_generations', function (Blueprint $table) {
            $table->dropColumn('source_packets');
        });
    }
};
