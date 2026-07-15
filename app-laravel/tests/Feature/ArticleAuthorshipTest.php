<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authorship rule for article creation: the author is the authenticated user,
 * and it is never taken from the request. author_id is deliberately kept out of
 * Article::$fillable so a client cannot set it — these tests hold both halves of
 * that in place (the fix, and the anti-spoofing it exists for).
 */
final class ArticleAuthorshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_article_is_attributed_to_the_authenticated_user(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->post('/admin/articles', [
                'title' => 'Mine',
                'body' => '<p>Body</p>',
                'published' => '1',
            ])
            ->assertRedirect();

        $article = Article::query()->latest('id')->firstOrFail();

        $this->assertSame($admin->id, $article->author_id);
    }

    public function test_an_author_id_supplied_in_the_request_is_ignored(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $someoneElse = User::factory()->create(['role' => Role::Editor]);

        $this->actingAs($admin)
            ->post('/admin/articles', [
                'title' => 'Still mine',
                'body' => '<p>Body</p>',
                'published' => '1',
                'author_id' => $someoneElse->id,   // an attempt to attribute it elsewhere
            ])
            ->assertRedirect();

        $article = Article::query()->latest('id')->firstOrFail();

        $this->assertSame($admin->id, $article->author_id);
        $this->assertNotSame($someoneElse->id, $article->author_id);
    }
}
