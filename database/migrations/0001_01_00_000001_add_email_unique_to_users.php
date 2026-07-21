<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_unique');
    }
};
