<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();

            // F-04: was VARCHAR(32) — sized for an MD5 digest, physically
            // unable to hold a bcrypt hash. Nullable during the migration
            // window: a user carried over from the old table has no bcrypt
            // hash yet, only a legacy digest.
            $table->string('password')->nullable();

            // Holds the old unsalted MD5 digest until the user next logs in,
            // at which point LegacyPasswordUpgrader replaces it. Hidden from
            // serialisation on the model.
            $table->string('legacy_password', 32)->nullable();

            // F-18: was a free-text VARCHAR.
            $table->enum('role', ['admin', 'editor', 'reader'])->default('reader');

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
