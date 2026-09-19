<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('category_translations', 'intro')) {
                $table->string('intro', 500)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('category_translations', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('intro');
            }
            if (! Schema::hasColumn('category_translations', 'meta_description')) {
                $table->string('meta_description', 500)->nullable()->after('meta_title');
            }
            if (! Schema::hasColumn('category_translations', 'og_title')) {
                $table->string('og_title', 255)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('category_translations', 'og_description')) {
                $table->string('og_description', 500)->nullable()->after('og_title');
            }
        });

        Schema::table('static_page_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('static_page_translations', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('title');
            }
            if (! Schema::hasColumn('static_page_translations', 'og_title')) {
                $table->string('og_title', 255)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('static_page_translations', 'og_description')) {
                $table->string('og_description', 500)->nullable()->after('og_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            foreach (['og_description', 'og_title', 'meta_description', 'meta_title', 'intro'] as $column) {
                if (Schema::hasColumn('category_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('static_page_translations', function (Blueprint $table) {
            foreach (['og_description', 'og_title', 'meta_title'] as $column) {
                if (Schema::hasColumn('static_page_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
