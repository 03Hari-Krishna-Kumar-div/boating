<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('rentals', 'extended_minutes')) {
                $table->integer('extended_minutes')->default(0);
            }
            if (!Schema::hasColumn('rentals', 'reduced_minutes')) {
                $table->integer('reduced_minutes')->default(0);
            }
            if (!Schema::hasColumn('rentals', 'ended_by')) {
                $table->foreignId('ended_by')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('rentals', 'end_reason')) {
                $table->string('end_reason')->nullable();
            }
            if (!Schema::hasColumn('rentals', 'admin_override')) {
                $table->boolean('admin_override')->default(false);
            }
            if (!Schema::hasColumn('rentals', 'extended_until')) {
                $table->timestamp('extended_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $columns = ['end_reason', 'admin_override', 'extended_until'];
            $existing = array_filter($columns, fn($c) => Schema::hasColumn('rentals', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
