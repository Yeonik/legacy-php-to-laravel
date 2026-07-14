<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // F-18: the legacy schema had no foreign keys at all, so deleting
            // an article left its comments behind forever.
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();

            $table->string('author');
            $table->text('body');
            $table->timestamps();

            $table->index('article_id');   // F-17
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
