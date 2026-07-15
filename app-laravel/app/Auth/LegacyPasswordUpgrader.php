<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Fixes F-04.
 *
 * The legacy table stores unsalted MD5 digests. Those cannot be converted to
 * bcrypt offline — the plaintext is not recoverable, and that is the whole
 * point of hashing.
 *
 * So we upgrade opportunistically. The one moment we legitimately hold the
 * plaintext is the instant the user types it. We verify it against the legacy
 * digest, and if it matches we immediately re-hash with bcrypt, persist that,
 * and drop the legacy digest.
 *
 * Users migrate silently as they log in. No forced reset, no mass email.
 * After the cutover window (see docs/MIGRATION.md) the stragglers are nulled
 * and go through password reset.
 */
final class LegacyPasswordUpgrader
{
    public function attempt(User $user, string $plaintext): bool
    {
        if (! $user->hasLegacyPassword()) {
            return false;
        }

        // hash_equals: constant-time comparison, so the check does not leak
        // information through its own timing.
        if (! hash_equals($user->legacy_password, md5($plaintext))) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($plaintext),
            'legacy_password' => null,
        ])->save();

        return true;
    }
}
