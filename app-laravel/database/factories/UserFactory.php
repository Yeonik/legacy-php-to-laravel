<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

final class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'            => fake()->name(),
            'email'           => fake()->unique()->safeEmail(),
            'password'        => Hash::make('password'),
            'legacy_password' => null,
            'role'            => Role::Reader,
        ];
    }

    /** A user carried over from the old table: MD5 digest, no bcrypt hash yet. */
    public function legacy(string $plaintext = 'password'): self
    {
        return $this->state(fn () => [
            'password'        => null,
            'legacy_password' => md5($plaintext),
        ]);
    }

    public function admin(): self
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }
}
