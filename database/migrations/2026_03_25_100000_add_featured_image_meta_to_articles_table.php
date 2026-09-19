<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'featured_image_alt')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('featured_image_alt', 255)->nullable()->after('featured_image');
            });
        }

        if (! Schema::hasColumn('articles', 'featured_image_caption')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('featured_image_caption', 500)->nullable()->after(
                    Schema::hasColumn('articles', 'featured_image_alt') ? 'featured_image_alt' : 'featured_image'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'featured_image_caption')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('featured_image_caption');
            });
        }
        if (Schema::hasColumn('articles', 'featured_image_alt')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('featured_image_alt');
            });
        }
    }
};
