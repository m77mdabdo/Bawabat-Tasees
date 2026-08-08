<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\HasSeoMetaRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    use HasSeoMetaRules;

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
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('articles', 'slug')->ignore($this->route('article')),
            ],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            'excerpt' => ['nullable', 'array'],
            'excerpt.ar' => ['nullable', 'string', 'max:500'],
            'excerpt.en' => ['nullable', 'string', 'max:500'],

            'body' => ['required', 'array'],
            'body.ar' => ['required', 'string', 'max:50000'],
            'body.en' => ['nullable', 'string', 'max:50000'],
        ] + $this->seoMetaRules();
    }
}
