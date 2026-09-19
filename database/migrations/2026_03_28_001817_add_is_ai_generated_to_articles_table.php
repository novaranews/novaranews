<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('articles', 'is_ai_generated')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_ai_generated')->default(false)->after('is_breaking');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_ai_generated');
        });
    }
};
