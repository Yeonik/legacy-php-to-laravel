<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = ['author', 'body'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
