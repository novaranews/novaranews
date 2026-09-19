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
            if (! Schema::hasColumn('users', 'show_public_email')) {
                $table->boolean('show_public_email')->default(false)->after('public_email');
            }
            if (! Schema::hasColumn('users', 'show_phone')) {
                $table->boolean('show_phone')->default(false)->after('phone');
            }
            if (! Schema::hasColumn('users', 'show_whatsapp')) {
                $table->boolean('show_whatsapp')->default(false)->after('whatsapp');
            }
            if (! Schema::hasColumn('users', 'show_telegram')) {
                $table->boolean('show_telegram')->default(false)->after('telegram');
            }
            if (! Schema::hasColumn('users', 'show_address')) {
                $table->boolean('show_address')->default(false)->after('address');
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
            foreach (['show_public_email', 'show_phone', 'show_whatsapp', 'show_telegram', 'show_address'] as $column) {
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
