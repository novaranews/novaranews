<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'public_email')) {
                $table->string('public_email', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 40)->nullable()->after('linkedin');
            }
            if (! Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp', 40)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'telegram')) {
                $table->string('telegram', 100)->nullable()->after('whatsapp');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address', 255)->nullable()->after('telegram');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $dropColumns = [];
            foreach (['public_email', 'phone', 'whatsapp', 'telegram', 'address'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
