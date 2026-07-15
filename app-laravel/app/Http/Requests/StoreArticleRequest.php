<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

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
     * F-03: the body is the one field this application renders as raw HTML
     * (resources/views/articles/show.blade.php). It is sanitised here — before
     * validation, and therefore before it is ever stored — through an allow-list
     * purifier. The persisted markup can only contain the formatting tags below;
     * anything else the client submits is dropped on the way in, so the stored
     * value is already safe by the time it reaches the view.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => Purifier::clean(
                (string) $this->input('body', ''),
                ['HTML.Allowed' => 'p,br,strong,em,b,i,u,ul,ol,li,a[href|title],h2,h3,blockquote,code,pre'],
            ),
        ]);
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
