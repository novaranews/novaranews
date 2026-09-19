<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_article_generations')) {
            Schema::create('ai_article_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_title', 255);
            $table->string('source_url', 2048)->nullable();
            $table->longText('source_text');
            $table->json('target_locales');
            $table->string('model', 120)->nullable();
            $table->string('status', 40)->default('pending');
            $table->text('error_message')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_article_generations');
    }
};

