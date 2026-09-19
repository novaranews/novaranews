<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('category_translations', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('og_description');
            }
            if (! Schema::hasColumn('category_translations', 'og_image_url')) {
                $table->string('og_image_url', 500)->nullable()->after('canonical_url');
            }
            if (! Schema::hasColumn('category_translations', 'robots_noindex')) {
                $table->boolean('robots_noindex')->default(false)->after('og_image_url');
            }
            if (! Schema::hasColumn('category_translations', 'robots_nofollow')) {
                $table->boolean('robots_nofollow')->default(false)->after('robots_noindex');
            }
        });

        Schema::table('static_page_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('static_page_translations', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('og_description');
            }
            if (! Schema::hasColumn('static_page_translations', 'og_image_url')) {
                $table->string('og_image_url', 500)->nullable()->after('canonical_url');
            }
            if (! Schema::hasColumn('static_page_translations', 'robots_noindex')) {
                $table->boolean('robots_noindex')->default(false)->after('og_image_url');
            }
            if (! Schema::hasColumn('static_page_translations', 'robots_nofollow')) {
                $table->boolean('robots_nofollow')->default(false)->after('robots_noindex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            foreach (['robots_nofollow', 'robots_noindex', 'og_image_url', 'canonical_url'] as $column) {
                if (Schema::hasColumn('category_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('static_page_translations', function (Blueprint $table) {
            foreach (['robots_nofollow', 'robots_noindex', 'og_image_url', 'canonical_url'] as $column) {
                if (Schema::hasColumn('static_page_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
