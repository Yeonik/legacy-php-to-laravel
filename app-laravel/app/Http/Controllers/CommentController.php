<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CommentController extends Controller
{
    public function store(Request $request, Article $article): RedirectResponse
    {
        abort_unless($article->published, 404);

        // The legacy version did none of this: no CSRF token (F-02), no
        // validation, no length limits, no escaping on the way back out (F-03).
        $data = $request->validate([
            'author' => ['required', 'string', 'max:80'],
            'body'   => ['required', 'string', 'max:2000'],
        ]);

        $article->comments()->create($data);

        return back()->with('status', 'Comment posted.');
    }
}
