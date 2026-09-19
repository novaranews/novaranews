<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if the column is already nullable (idempotent re-deploy guard)
        if (DB::getDriverName() === 'mysql') {
            $isNullable = collect(
                DB::select("SHOW COLUMNS FROM `articles` LIKE 'user_id'")
            )->first()?->Null ?? 'NO';

            if ($isNullable === 'YES') {
                return;
            }
        }

        Schema::table('articles', function (Blueprint $table) {
            // Bot-generated articles have no human author — allow null
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
