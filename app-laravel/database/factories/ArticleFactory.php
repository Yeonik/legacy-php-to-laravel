<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(4)).'</p>',
            'published' => true,
            'views' => 0,
        ];
    }
}
