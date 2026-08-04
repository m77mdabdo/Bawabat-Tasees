<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    /**
     * Route-level ['auth', 'admin'] middleware already gates every
     * dashboard route, so authorization here is intentionally a pass-through.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:articles,slug'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            // excerpt is nullable end-to-end per the articles migration
            // (unlike title/body, which are required columns).
            'excerpt' => ['nullable', 'array'],
            'excerpt.ar' => ['nullable', 'string', 'max:500'],
            'excerpt.en' => ['nullable', 'string', 'max:500'],

            // Real HTML is allowed here (sanitized in the controller via
            // HtmlSanitizerService before persisting) — max length is
            // generous to account for markup overhead.
            'body' => ['required', 'array'],
            'body.ar' => ['required', 'string', 'max:50000'],
            'body.en' => ['nullable', 'string', 'max:50000'],
        ];
    }
}
