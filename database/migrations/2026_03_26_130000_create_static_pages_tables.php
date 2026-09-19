<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('static_pages')) {
            Schema::create('static_pages', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('static_page_translations')) {
            Schema::create('static_page_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('static_page_id')->constrained('static_pages')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('meta_description', 500)->nullable();
                $table->longText('content')->nullable();
                $table->timestamps();
                $table->unique(['static_page_id', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('static_page_translations');
        Schema::dropIfExists('static_pages');
    }
};
