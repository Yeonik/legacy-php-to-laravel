<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Fixes F-05.
 *
 * Legacy: `UPDATE articles SET views = views + 1` synchronously on every page
 * view — a write on the hot read path, taking a table-level lock on MyISAM.
 *
 * Here the increment is queued. The read request returns without touching the
 * write path at all.
 */
final class RecordArticleView implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $articleId) {}

    public function handle(): void
    {
        // Atomic increment — no read-modify-write race.
        DB::table('articles')
            ->where('id', $this->articleId)
            ->increment('views');
    }

    /** Collapse duplicate views of the same article within the same window. */
    public function uniqueId(): string
    {
        return (string) $this->articleId;
    }
}
