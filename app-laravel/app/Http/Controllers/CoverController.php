<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * F-11, the second half.
 *
 * Legacy stored uploads in a web-accessible directory under their original,
 * client-supplied filename. Anything that landed there was served by Apache.
 *
 * Here covers sit on a private disk and are streamed by PHP, so the web server
 * never executes them regardless of what they turn out to contain.
 */
final class CoverController extends Controller
{
    public function __invoke(Article $article): StreamedResponse
    {
        abort_if($article->cover_path === null, 404);
        abort_unless(Storage::disk('covers')->exists($article->cover_path), 404);

        return Storage::disk('covers')->response(
            $article->cover_path,
            headers: ['Content-Disposition' => 'inline'],
        );
    }
}
