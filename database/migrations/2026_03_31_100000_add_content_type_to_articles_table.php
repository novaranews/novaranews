<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('articles', 'content_type')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->string('content_type', 32)->default('news')->after('is_editors_pick');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('content_type');
        });
    }
};
