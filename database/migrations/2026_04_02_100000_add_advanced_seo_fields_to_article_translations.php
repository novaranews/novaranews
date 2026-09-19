<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_translations')) {
            return;
        }

        Schema::table('article_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('article_translations', 'og_title')) {
                $table->string('og_title', 255)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('article_translations', 'og_description')) {
                $table->string('og_description', 500)->nullable()->after('og_title');
            }
            if (! Schema::hasColumn('article_translations', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('og_description');
            }
            if (! Schema::hasColumn('article_translations', 'og_image_url')) {
                $table->string('og_image_url', 500)->nullable()->after('canonical_url');
            }
            if (! Schema::hasColumn('article_translations', 'robots_noindex')) {
                $table->boolean('robots_noindex')->default(false)->after('og_image_url');
            }
            if (! Schema::hasColumn('article_translations', 'robots_nofollow')) {
                $table->boolean('robots_nofollow')->default(false)->after('robots_noindex');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('article_translations')) {
            return;
        }

        Schema::table('article_translations', function (Blueprint $table) {
            foreach (['robots_nofollow', 'robots_noindex', 'og_image_url', 'canonical_url', 'og_description', 'og_title'] as $col) {
                if (Schema::hasColumn('article_translations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
