<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    /**
     * Fix the SQLite CHECK constraint on boats.status.
     *
     * The original 0001_01_01_000000_create_boats_table migration created an
     * enum('available','occupied','warning','awaiting_confirmation','overdue','maintenance')
     * column.  SQLite implements Laravel's enum() as a TEXT column with a CHECK constraint
     * listing those values.
     *
     * Later migrations added two new statuses — 'ended' and 'time_up' — to the PHP
     * BoatStatus enum and updated the MySQL column definition, but the SQLite CHECK
     * constraint was never refreshed.  Any attempt to set a boat's status to 'ended'
     * or 'time_up' therefore throws:
     *   CHECK constraint failed: status
     *
     * This migration recreates the boats table with the full list of status values
     * so that endRental(), markTimeUp(), and any other operation using the new
     * statuses works on SQLite.
     *
     * Because SQLite does not support ALTER TABLE … ADD CHECK, the table is
     * recreated via the standard rename/copy/drop approach.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // MySQL handled by the earlier migration
            return;
        }

        // Disable foreign key checks while we swap tables
        DB::statement('PRAGMA foreign_keys = OFF');

        // 1. Create the new table with the updated CHECK constraint
        Schema::create('boats_new', function (Blueprint $table) {
            $table->id();
            $table->integer('boat_number')->unique();
            $table->string('name')->nullable();
            $table->string('status', 30)->default('available')->index();
            $table->unsignedBigInteger('current_rental_id')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Copy all rows (column order matches the original schema)
        DB::statement('INSERT INTO boats_new (id, boat_number, name, status, current_rental_id, color_hex, notes, created_at, updated_at) SELECT id, boat_number, name, status, current_rental_id, color_hex, notes, created_at, updated_at FROM boats');

        // 3. Drop the old table
        Schema::drop('boats');

        // 4. Rename new table to original name
        Schema::rename('boats_new', 'boats');

        // 5. Re-create indexes that were lost during the rename
        //    (the unique index and status index were already defined in the create above,
        //     but SQLite drops them on rename — so we re-add them)
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS boats_boat_number_unique ON boats (boat_number)');
            DB::statement('CREATE INDEX IF NOT EXISTS boats_status_index ON boats (status)');
        } catch (\Exception $e) {
            // Indexes may already exist depending on SQLite version; ignore
        }

        // 6. Re-enable foreign keys
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        // Reverse: restore the original CHECK constraint
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('boats_old', function (Blueprint $table) {
            $table->id();
            $table->integer('boat_number')->unique();
            $table->string('name')->nullable();
            $table->string('status', 30)->default('available')->index();
            $table->unsignedBigInteger('current_rental_id')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO boats_old SELECT * FROM boats');
        Schema::drop('boats');
        Schema::rename('boats_old', 'boats');

        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS boats_boat_number_unique ON boats (boat_number)');
            DB::statement('CREATE INDEX IF NOT EXISTS boats_status_index ON boats (status)');
        } catch (\Exception $e) {
            // Ignore
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
