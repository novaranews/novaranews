<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'is_breaking')) {
            $after = 'breaking_text';
            if (! Schema::hasColumn('articles', 'breaking_text')) {
                $after = Schema::hasColumn('articles', 'featured_image_caption')
                    ? 'featured_image_caption'
                    : (Schema::hasColumn('articles', 'featured_image_alt') ? 'featured_image_alt' : 'featured_image');
            }

            Schema::table('articles', function (Blueprint $table) use ($after) {
                $table->boolean('is_breaking')->default(false)->after($after);
            });
        }

        if (Schema::hasColumn('articles', 'breaking_text')) {
            DB::table('articles')
                ->whereNotNull('breaking_text')
                ->where('breaking_text', '!=', '')
                ->update(['is_breaking' => true]);
        }

        $drops = array_values(array_filter([
            Schema::hasColumn('articles', 'breaking_text') ? 'breaking_text' : null,
            Schema::hasColumn('articles', 'check_5w1h') ? 'check_5w1h' : null,
            Schema::hasColumn('articles', 'check_sources') ? 'check_sources' : null,
            Schema::hasColumn('articles', 'check_originality') ? 'check_originality' : null,
            Schema::hasColumn('articles', 'check_locale_quality') ? 'check_locale_quality' : null,
        ]));

        if ($drops !== []) {
            Schema::table('articles', function (Blueprint $table) use ($drops) {
                $table->dropColumn($drops);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'is_breaking')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('is_breaking');
            });
        }

        if (! Schema::hasColumn('articles', 'breaking_text')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('breaking_text')->nullable()->after('featured_image_caption');
            });
        }
        if (! Schema::hasColumn('articles', 'check_5w1h')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->boolean('check_5w1h')->default(false)->after('breaking_text');
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
    }
};
