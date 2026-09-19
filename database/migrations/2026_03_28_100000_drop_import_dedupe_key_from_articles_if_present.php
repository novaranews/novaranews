<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eski kurulumlarda import_dedupe_key sütunu kaldıysa temizler (JSON import özelliği kaldırıldı).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'import_dedupe_key')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['import_dedupe_key']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('import_dedupe_key');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('articles', 'import_dedupe_key')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('import_dedupe_key', 191)->nullable()->after('user_id');
            });
            Schema::table('articles', function (Blueprint $table) {
                $table->unique('import_dedupe_key', 'articles_import_dedupe_key_unique');
            });
        }
    }
};
