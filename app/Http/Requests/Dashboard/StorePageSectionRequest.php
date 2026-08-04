<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class StorePageSectionRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // These map into the page_sections.content JSON column in the
            // controller, not directly to DB columns — always plain text,
            // rendered via {{ }}, never {!! !!}, so no sanitizer needed.
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],

            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }
}
