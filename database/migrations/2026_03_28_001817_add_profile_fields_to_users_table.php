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
        if (Schema::hasColumn('users', 'slug')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('slug', 100)->nullable()->unique()->after('name');
            $table->string('title', 100)->nullable()->after('slug');       // e.g. "Baş Editör"
            $table->text('bio')->nullable()->after('title');
            $table->string('avatar', 500)->nullable()->after('bio');       // storage path
            $table->string('twitter', 100)->nullable()->after('avatar');   // username only
            $table->string('linkedin', 100)->nullable()->after('twitter'); // username only
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['slug', 'title', 'bio', 'avatar', 'twitter', 'linkedin']);
        });
    }
};
