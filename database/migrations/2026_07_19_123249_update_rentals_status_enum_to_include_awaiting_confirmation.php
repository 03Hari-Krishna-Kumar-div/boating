<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;
    /**
     * Run the migrations.
     * Adds 'awaiting_confirmation' to the rentals.status ENUM.
     * For SQLite (dev), ENUM is treated as TEXT so no change needed.
     * For MySQL (prod), we must ALTER the column.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('active', 'completed', 'overdue', 'overridden', 'awaiting_confirmation') NOT NULL DEFAULT 'active'");
        }
        // SQLite: ENUM is stored as TEXT with no constraint — no change needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN status ENUM('active', 'completed', 'overdue', 'overridden') NOT NULL DEFAULT 'active'");
        }
        // SQLite: no change needed
    }
};
