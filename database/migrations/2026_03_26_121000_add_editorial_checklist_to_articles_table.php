<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $after = 'breaking_text';
        if (! Schema::hasColumn('articles', 'breaking_text')) {
            $after = Schema::hasColumn('articles', 'featured_image_caption')
                ? 'featured_image_caption'
                : (Schema::hasColumn('articles', 'featured_image_alt') ? 'featured_image_alt' : 'featured_image');
        }

        if (! Schema::hasColumn('articles', 'check_5w1h')) {
            Schema::table('articles', function (Blueprint $table) use ($after) {
                $table->boolean('check_5w1h')->default(false)->after($after);
            });
        }
        if (! Schema::hasColumn('articles', 'check_sources')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->boolean('check_sources')->default(false)->after('check_5w1h');
            });
        }
        if (! Schema::hasColumn('articles', 'check_originality')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->boolean('check_originality')->default(false)->after('check_sources');
            });
        }
        if (! Schema::hasColumn('articles', 'check_locale_quality')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->boolean('check_locale_quality')->default(false)->after('check_originality');
            });
        }
        if (! Schema::hasColumn('articles', 'editor_reviewed_at')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->timestamp('editor_reviewed_at')->nullable()->after('check_locale_quality');
            });
        }
    }

    public function down(): void
    {
        $cols = array_values(array_filter([
            Schema::hasColumn('articles', 'editor_reviewed_at') ? 'editor_reviewed_at' : null,
            Schema::hasColumn('articles', 'check_locale_quality') ? 'check_locale_quality' : null,
            Schema::hasColumn('articles', 'check_originality') ? 'check_originality' : null,
            Schema::hasColumn('articles', 'check_sources') ? 'check_sources' : null,
            Schema::hasColumn('articles', 'check_5w1h') ? 'check_5w1h' : null,
        ]));
        if ($cols === []) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) use ($cols) {
            $table->dropColumn($cols);
        });
    }
};
