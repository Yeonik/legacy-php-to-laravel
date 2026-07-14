<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',   // password: password
        ]);

        // A user as they arrive from the legacy table: MD5 only.
        // Log in as legacy@example.com / password and watch the row change:
        // `legacy_password` goes NULL and `password` becomes a bcrypt hash.
        User::factory()->legacy()->create([
            'email' => 'legacy@example.com',
        ]);

        Article::factory()->count(40)->create(['author_id' => $admin->id]);
    }
}
