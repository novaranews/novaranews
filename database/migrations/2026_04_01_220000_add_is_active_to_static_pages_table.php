<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('static_pages') && ! Schema::hasColumn('static_pages', 'is_active')) {
            Schema::table('static_pages', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('static_pages') && Schema::hasColumn('static_pages', 'is_active')) {
            Schema::table('static_pages', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
