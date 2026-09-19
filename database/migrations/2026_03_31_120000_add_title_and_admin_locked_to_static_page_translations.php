<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('static_page_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('static_page_translations', 'title')) {
                $table->string('title', 255)->nullable()->after('locale');
            }
            if (! Schema::hasColumn('static_page_translations', 'admin_locked_at')) {
                $table->timestamp('admin_locked_at')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('static_page_translations', function (Blueprint $table) {
            if (Schema::hasColumn('static_page_translations', 'admin_locked_at')) {
                $table->dropColumn('admin_locked_at');
            }
            if (Schema::hasColumn('static_page_translations', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
