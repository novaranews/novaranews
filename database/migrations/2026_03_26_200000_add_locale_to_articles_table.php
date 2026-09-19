<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $localeWasNew = false;

        if (! Schema::hasColumn('articles', 'locale')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('locale', 16)->default('en')->after('user_id');
            });
            $localeWasNew = true;
        }

        if ($localeWasNew && Schema::hasTable('article_translations')) {
            $default = (string) config('novaranews.default_locale', 'en');
            $locales = config('novaranews.locales', ['en']);
            if (! is_array($locales) || $locales === []) {
                $locales = ['en'];
            }

            $ids = DB::table('articles')->pluck('id');
            foreach ($ids as $id) {
                $rows = DB::table('article_translations')->where('article_id', $id)->get();
                $chosen = null;
                foreach ($locales as $l) {
                    if ($rows->contains(fn ($r) => (string) $r->locale === $l)) {
                        $chosen = $l;
                        break;
                    }
                }
                if ($chosen === null && $rows->isNotEmpty()) {
                    $chosen = (string) $rows->first()->locale;
                }
                if ($chosen === null || $chosen === '') {
                    $chosen = $default;
                }
                DB::table('articles')->where('id', $id)->update(['locale' => $chosen]);
                DB::table('article_translations')->where('article_id', $id)->where('locale', '!=', $chosen)->delete();
            }
        }

        if (! Schema::hasIndex('articles', ['locale', 'status', 'published_at'])) {
            Schema::table('articles', function (Blueprint $table) {
                $table->index(['locale', 'status', 'published_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('articles', ['locale', 'status', 'published_at'])) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropIndex(['locale', 'status', 'published_at']);
            });
        }

        if (Schema::hasColumn('articles', 'locale')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('locale');
            });
        }
    }
};
