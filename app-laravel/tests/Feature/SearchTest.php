<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for F-01 and F-03 on the search path.
 *
 * These assert the *outcome* — the application stays intact and the term is
 * escaped on the way back into the page. They contain no attack payloads;
 * the strings below are ordinary awkward input, which is all a regression
 * test needs.
 */
final class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_search_term_with_sql_metacharacters_is_treated_as_text(): void
    {
        Article::factory()->create(['title' => 'Laravel', 'published' => true]);

        // A quote is data, not syntax. Legacy: 500 and a printed SQL error.
        $this->get('/?q='.urlencode("O'Reilly"))
            ->assertOk();

        $this->assertDatabaseCount('articles', 1);
    }

    public function test_the_search_term_is_escaped_when_echoed_back(): void
    {
        $term = '<b>bold</b>';

        $response = $this->get('/?q='.urlencode($term));

        // Blade escapes it: the literal tag must not survive into the markup.
        $response->assertOk();
        $response->assertDontSee('<b>bold</b>', escape: false);
        $response->assertSee('&lt;b&gt;bold&lt;/b&gt;', escape: false);
    }

    public function test_the_listing_is_paginated(): void
    {
        Article::factory()->count(30)->create(['published' => true]);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('articles', fn ($articles) => $articles->count() === 15);
    }
}
