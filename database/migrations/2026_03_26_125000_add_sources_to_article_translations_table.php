<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('article_translations', 'sources')) {
            return;
        }

        Schema::table('article_translations', function (Blueprint $table) {
            $table->json('sources')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('article_translations', 'sources')) {
            return;
        }

        Schema::table('article_translations', function (Blueprint $table) {
            $table->dropColumn('sources');
        });
    }
};
