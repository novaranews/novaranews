<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_article_generations', 'input_tokens')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $table->unsignedInteger('input_tokens')->nullable()->after('raw_response');
            });
        }
        if (! Schema::hasColumn('ai_article_generations', 'output_tokens')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $table->unsignedInteger('output_tokens')->nullable()->after(
                    Schema::hasColumn('ai_article_generations', 'input_tokens') ? 'input_tokens' : 'raw_response'
                );
            });
        }
        if (! Schema::hasColumn('ai_article_generations', 'estimated_cost_usd')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $table->decimal('estimated_cost_usd', 10, 6)->nullable()->after(
                    Schema::hasColumn('ai_article_generations', 'output_tokens') ? 'output_tokens' : 'raw_response'
                );
            });
        }
    }

    public function down(): void
    {
        foreach (['estimated_cost_usd', 'output_tokens', 'input_tokens'] as $col) {
            if (Schema::hasColumn('ai_article_generations', $col)) {
                Schema::table('ai_article_generations', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
