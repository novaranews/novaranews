<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('static_page_translations', 'slug')) {
            return;
        }

        Schema::table('static_page_translations', function (Blueprint $table) {
            $table->string('slug', 100)->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('static_page_translations', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
