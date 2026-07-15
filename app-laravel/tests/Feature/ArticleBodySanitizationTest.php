<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for F-03. The article body is the single field this
 * application renders as raw HTML, so it is passed through an allow-list
 * purifier on the way in. This asserts the outcome of that allow-list —
 * formatting is kept, anything off the list is not — without exercising any
 * attack.
 */
final class ArticleBodySanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_stored_body_keeps_allow_listed_tags_and_drops_the_rest(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        // <marquee> is simply not on the allow-list — a formatting choice, not an
        // attack. The point is that a tag the author did not need is not stored.
        $this->actingAs($admin)
            ->post('/admin/articles', [
                'title' => 'Formatting is kept, stray tags are not',
                'body' => '<p>Kept paragraph</p><strong>bold</strong><marquee>scrolling</marquee>',
                'published' => '1',
            ])
            ->assertRedirect();

        $body = Article::query()->latest('id')->firstOrFail()->body;

        // Allow-listed formatting survives round-tripping through the purifier…
        $this->assertStringContainsString('<p>Kept paragraph</p>', $body);
        $this->assertStringContainsString('<strong>bold</strong>', $body);

        // …and a tag that is not on the list is gone from the stored value.
        $this->assertStringNotContainsString('<marquee', $body);
    }
}
