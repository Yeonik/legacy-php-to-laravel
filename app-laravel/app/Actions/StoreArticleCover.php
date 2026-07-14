<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Fixes F-11 — defence in depth for the cover upload.
 *
 * The legacy code did this:
 *     move_uploaded_file($_FILES['cover']['tmp_name'], '../uploads/' . $_FILES['cover']['name']);
 *
 * — client-supplied filename, no type check, no size cap, and the destination
 * was served by the web server. Four separate mistakes in one line.
 *
 * Validation (StoreArticleRequest) is the first layer. This is the second:
 * even if a file gets past the rules, it lands somewhere that cannot execute.
 */
final class StoreArticleCover
{
    /** Disk is configured with root storage/app/private — outside the document root. */
    private const DISK = 'covers';

    public function __invoke(UploadedFile $file): string
    {
        // Storage::putFile generates the name. The original is discarded —
        // it is attacker-controlled and there is no reason to trust it.
        $path = Storage::disk(self::DISK)->putFile('', $file);

        if ($path === false) {
            throw new \RuntimeException('Failed to store cover image.');
        }

        return $path;
    }
}
