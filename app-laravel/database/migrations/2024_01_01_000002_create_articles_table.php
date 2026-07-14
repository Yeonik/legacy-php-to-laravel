<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');

            // Path on a private disk, not a filename in a public directory.
            $table->string('cover_path')->nullable();

            $table->boolean('published')->default(false);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();

            // F-17: the listing filters on `published` and sorts on `created_at`
            // on every single request. The legacy table had neither index.
            $table->index(['published', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
