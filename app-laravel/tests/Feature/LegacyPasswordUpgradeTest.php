<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for F-04 — the transparent MD5 -> bcrypt upgrade.
 *
 * This is the piece that decides whether a rewrite can actually ship: you
 * cannot re-hash passwords you do not have, so users must migrate themselves,
 * without noticing.
 */
final class LegacyPasswordUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legacy_user_can_log_in_with_their_old_password(): void
    {
        $user = User::factory()->create([
            'email'           => 'old@example.com',
            'password'        => null,
            'legacy_password' => md5('correct horse battery staple'),
        ]);

        $this->post('/login', [
            'email'    => 'old@example.com',
            'password' => 'correct horse battery staple',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_logging_in_silently_upgrades_the_hash_to_bcrypt(): void
    {
        $user = User::factory()->create([
            'email'           => 'old@example.com',
            'password'        => null,
            'legacy_password' => md5('correct horse battery staple'),
        ]);

        $this->post('/login', [
            'email'    => 'old@example.com',
            'password' => 'correct horse battery staple',
        ]);

        $user->refresh();

        $this->assertNull($user->legacy_password, 'legacy digest must be dropped');
        $this->assertNotNull($user->password);
        $this->assertTrue(Hash::check('correct horse battery staple', $user->password));
    }

    public function test_a_wrong_password_does_not_upgrade_anything(): void
    {
        $user = User::factory()->create([
            'password'        => null,
            'legacy_password' => md5('right'),
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors();

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->legacy_password);
    }
}
