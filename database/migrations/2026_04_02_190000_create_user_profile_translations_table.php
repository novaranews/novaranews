<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profile_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 100)->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'locale']);
        });

        $defaultLocale = config('novaranews.default_locale', 'en');

        foreach (DB::table('users')->select(['id', 'title', 'bio'])->cursor() as $row) {
            $title = $row->title ?? null;
            $bio = $row->bio ?? null;
            if (($title === null || $title === '') && ($bio === null || $bio === '')) {
                continue;
            }
            DB::table('user_profile_translations')->insert([
                'user_id' => $row->id,
                'locale' => $defaultLocale,
                'title' => $title,
                'bio' => $bio,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profile_translations');
    }
};
