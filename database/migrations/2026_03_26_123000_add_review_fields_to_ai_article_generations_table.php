<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ai_article_generations', 'approved_by_user_id')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $after = Schema::hasColumn('ai_article_generations', 'estimated_cost_usd')
                    ? 'estimated_cost_usd'
                    : 'raw_response';
                $table->foreignId('approved_by_user_id')->nullable()->after($after)->constrained('users')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('ai_article_generations', 'approval_note')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $table->text('approval_note')->nullable()->after(
                    Schema::hasColumn('ai_article_generations', 'approved_by_user_id') ? 'approved_by_user_id' : 'raw_response'
                );
            });
        }
        if (! Schema::hasColumn('ai_article_generations', 'approved_at')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after(
                    Schema::hasColumn('ai_article_generations', 'approval_note') ? 'approval_note' : 'raw_response'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_article_generations', 'approved_by_user_id')) {
            Schema::table('ai_article_generations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by_user_id');
            });
        }
        foreach (['approved_at', 'approval_note'] as $col) {
            if (Schema::hasColumn('ai_article_generations', $col)) {
                Schema::table('ai_article_generations', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
