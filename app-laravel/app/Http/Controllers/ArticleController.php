<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RecordArticleView;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public side. Compare with legacy/public/index.php + article.php.
 */
final class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();

        $articles = Article::query()
            ->select(Article::LIST_COLUMNS)   // F-13: no SELECT *
            ->published()
            ->when($term !== '', function ($query) use ($term) {
                // F-01: bound parameter. The value never becomes SQL.
                return $query->where('title', 'like', '%'.$term.'%');
            })
            ->latest()
            ->paginate(15)                    // F-12: was: load the whole table
            ->withQueryString();

        // F-03: Blade escapes $term on the way back into the search box.
        return view('articles.index', compact('articles', 'term'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->published, 404);

        // F-05: the read path performs no writes. The counter is queued
        // and batched instead of issuing an UPDATE on every page view.
        RecordArticleView::dispatch($article->id)->afterResponse();

        $article->load(['comments' => fn ($q) => $q->latest()->limit(50)]);

        return view('articles.show', compact('article'));
    }
}
