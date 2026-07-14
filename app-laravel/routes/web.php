<?php

declare(strict_types=1);

use App\Http\Controllers\AdminArticleController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CoverController;
use Illuminate\Support\Facades\Route;

/*
 * Compare with the legacy routing model, which was "one .php file per URL,
 * with an $_GET['action'] switch inside". Notably: legacy deletion was
 *     admin.php?action=delete&id=7
 * — a destructive write behind a GET link, with no CSRF token (F-02, F-10).
 */

Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');

// F-08: rate limited. The legacy login had no throttle, no lockout, no logging.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// F-02: POST, CSRF-protected. F-08: throttled — the legacy comment form was
// an open, unauthenticated, unvalidated INSERT.
Route::post('/articles/{article}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('comments.store');

// F-11: covers live on a private disk, outside the document root, and are
// served through a controller rather than by the web server.
Route::get('/covers/{article}', CoverController::class)->name('covers.show');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/articles', [AdminArticleController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Article')
        ->name('articles.index');

    Route::post('/articles', [AdminArticleController::class, 'store'])
        ->middleware('can:create,App\Models\Article')
        ->name('articles.store');

    // F-10: DELETE verb, not a GET link.
    Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])
        ->middleware('can:delete,article')
        ->name('articles.destroy');
});
