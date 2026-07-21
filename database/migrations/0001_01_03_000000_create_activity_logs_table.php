<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('boat_id')->nullable();
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->string('action', 100)->index();
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->index();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('boat_id')->references('id')->on('boats')->onDelete('set null');
            $table->foreign('rental_id')->references('id')->on('rentals')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
