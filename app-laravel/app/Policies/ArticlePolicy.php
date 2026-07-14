<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Fixes F-09.
 *
 * The legacy admin panel had exactly one guard — isset($_SESSION['user_id']).
 * That is an *authentication* check standing in for an *authorisation* check,
 * which meant every registered user had full admin. Separating the two is the
 * entire fix.
 */
final class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->role->value === 'editor';
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Article $article): bool
    {
        return $user->isAdmin() || $article->author_id === $user->id;
    }

    public function delete(User $user, Article $article): bool
    {
        // Deleting is admin-only. In the legacy app it was a GET link.
        return $user->isAdmin();
    }
}
