<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Fixes F-11 and part of F-14.
 *
 * In the legacy app there was nowhere to put a validation rule, so no
 * validation was written. Giving it a home is most of the battle.
 */
final class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Article::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'published' => ['boolean'],

            // F-11: allow-list of extensions, server-side MIME check, size cap.
            // The client-supplied filename is never used — storage generates one.
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
