<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['user_id', 'read_at']);
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('rentals', function (Blueprint $table) {
                $table->index(['worker_id', 'status']);
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('boats', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('last_activity_at');
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        try { Schema::table('notifications', fn($t) => $t->dropIndex(['user_id', 'read_at'])); } catch (\Exception $e) {}
        try { Schema::table('rentals', fn($t) => $t->dropIndex(['worker_id', 'status'])); } catch (\Exception $e) {}
        try { Schema::table('boats', fn($t) => $t->dropIndex(['status'])); } catch (\Exception $e) {}
        try { Schema::table('users', fn($t) => $t->dropIndex(['last_activity_at'])); } catch (\Exception $e) {}
    }
};
