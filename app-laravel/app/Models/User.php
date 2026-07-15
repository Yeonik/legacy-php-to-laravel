<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'legacy_password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'email_verified_at' => 'datetime',
            'role'              => Role::class,
        ];
    }

    /**
     * Fixes F-07: the role is read from the model on every check, never
     * cached in the session. Revoking admin in the database takes effect
     * on the next request, not on the next login.
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function hasLegacyPassword(): bool
    {
        return $this->legacy_password !== null;
    }
}
