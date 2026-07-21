<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX personal_access_tokens_token_unique ON personal_access_tokens (token)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS personal_access_tokens_token_unique');
    }
};
