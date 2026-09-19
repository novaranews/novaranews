<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_redirects')) {
            return;
        }

        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 8)->nullable();
            $table->string('from_path', 500);
            $table->string('to_url', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['locale', 'from_path']);
            $table->index(['is_active', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_redirects');
    }
};
