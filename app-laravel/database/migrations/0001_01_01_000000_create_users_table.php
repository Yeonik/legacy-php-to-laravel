<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The `users` table is created by 2024_01_01_000001_create_users_table.php,
        // not here. Do NOT reinstate the skeleton's version: it sorts first by name
        // (0001… < 2024…), so it would either collide ("table already exists") or,
        // if it won, ship a `users` table without the `legacy_password` column —
        // leaving LegacyPasswordUpgrader (F-04) nowhere to write, which kills the
        // one feature this case study exists to demonstrate.
        // password_reset_tokens and sessions below are framework infrastructure and
        // stay here; sessions.user_id is nullable with no FK, so table order is moot.

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // `users` is dropped by 2024_01_01_000001_create_users_table.php — see up().
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
