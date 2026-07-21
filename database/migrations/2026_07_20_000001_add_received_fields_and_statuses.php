<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add received fields to rentals
        Schema::table('rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('rentals', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('actual_end_at');
            }
            if (!Schema::hasColumn('rentals', 'received_by_worker_id')) {
                $table->unsignedBigInteger('received_by_worker_id')->nullable()->after('received_at');
                $table->foreign('received_by_worker_id')->references('id')->on('users')->onDelete('set null');
            }
        });

        // SQLite stores ENUM as TEXT + CHECK constraint, so the original
        // CREATE TABLE CHECK constraint still restricts status values.
        // See migration 2026_07_20_000002 for the SQLite fix.
        // MySQL: update ENUM values
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('active','completed','overdue','overridden','awaiting_confirmation','ended') NOT NULL DEFAULT 'active'");
            DB::statement("ALTER TABLE boats MODIFY COLUMN status ENUM('available','occupied','warning','awaiting_confirmation','overdue','maintenance','ended','time_up') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            if (Schema::hasColumn('rentals', 'received_by_worker_id')) {
                $table->dropForeign(['received_by_worker_id']);
                $table->dropColumn('received_by_worker_id');
            }
            if (Schema::hasColumn('rentals', 'received_at')) {
                $table->dropColumn('received_at');
            }
        });
    }
};
