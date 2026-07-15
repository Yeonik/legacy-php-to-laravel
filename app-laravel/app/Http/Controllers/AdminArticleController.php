<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StoreArticleCover;
use App\Http\Requests\StoreArticleRequest;
use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Compare with legacy/public/admin.php — 90 lines holding authorisation,
 * routing, validation (none), file handling, SQL and HTML.
 *
 * Authorisation is not here. It is in ArticlePolicy, enforced by the `can:`
 * middleware on the routes. Keeping it out of the controller is what stops it
 * from degenerating back into `isset($_SESSION['user_id'])` (F-09).
 */
final class AdminArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->select(Article::LIST_COLUMNS)
            ->latest()
            ->paginate(20);

        return view('admin.index', compact('articles'));
    }

    public function store(StoreArticleRequest $request, StoreArticleCover $storeCover): RedirectResponse
    {
        // $request->validated() — only allow-listed keys. The legacy code
        // assigned straight from $_POST, which is how `published` and `views`
        // became writable by anyone who guessed the field name.
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $storeCover($request->file('cover'));
        }

        $data['slug'] = Str::slug($data['title']).'-'.Str::random(6);

        $article = new Article($data);
        // author_id is deliberately NOT in $fillable — it is the authenticated
        // user, never a request field. Setting it directly (rather than through
        // mass assignment) is both why a client cannot forge it and why it is
        // not silently dropped on the way to the database.
        $article->author_id = $request->user()->id;
        $article->save();

        return redirect()
            ->route('admin.articles.index')
            ->with('status', 'Article created.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        // Authorisation already enforced by `can:delete,article` on the route.
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('status', 'Article deleted.');
    }
}
