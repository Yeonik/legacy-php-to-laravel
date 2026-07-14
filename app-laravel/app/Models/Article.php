<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    /**
     * Mass-assignment allow-list. The legacy code assigned straight from
     * $_POST, which is how `published` and `views` became writable by anyone
     * who could guess a field name.
     */
    protected $fillable = ['title', 'slug', 'body', 'cover_path', 'published'];

    protected $casts = [
        'published' => 'boolean',
        'views'     => 'integer',
    ];

    /** Columns a listing needs. Fixes F-13 (SELECT *). */
    public const LIST_COLUMNS = ['id', 'title', 'slug', 'cover_path', 'created_at'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
