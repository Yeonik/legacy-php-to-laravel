<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for F-09.
 *
 * In the legacy app the admin panel's only guard was
 * `isset($_SESSION['user_id'])`, so any registered reader had full admin,
 * including delete. These tests exist so that hole cannot come back.
 */
final class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reader_cannot_open_the_admin_panel(): void
    {
        $reader = User::factory()->create(['role' => Role::Reader]);

        $this->actingAs($reader)
            ->get('/admin/articles')
            ->assertForbidden();          // legacy behaviour: 200 OK
    }

    public function test_a_reader_cannot_delete_an_article(): void
    {
        $reader  = User::factory()->create(['role' => Role::Reader]);
        $article = Article::factory()->create();

        $this->actingAs($reader)
            ->delete("/admin/articles/{$article->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_an_editor_cannot_delete_someone_elses_article(): void
    {
        $editor  = User::factory()->create(['role' => Role::Editor]);
        $article = Article::factory()->create();   // authored by someone else

        $this->actingAs($editor)
            ->delete("/admin/articles/{$article->id}")
            ->assertForbidden();
    }

    public function test_an_admin_can_delete_an_article(): void
    {
        $admin   = User::factory()->create(['role' => Role::Admin]);
        $article = Article::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/articles/{$article->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    /**
     * F-07: the legacy code copied the role into $_SESSION at login and never
     * looked at the database again — so demoting an admin did nothing until
     * their session expired.
     */
    public function test_revoking_admin_takes_effect_on_the_next_request(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($user)->get('/admin/articles')->assertOk();

        $user->update(['role' => Role::Reader]);

        $this->actingAs($user->fresh())->get('/admin/articles')->assertForbidden();
    }
}
