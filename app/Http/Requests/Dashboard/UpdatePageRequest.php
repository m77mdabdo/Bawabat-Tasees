<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\HasSeoMetaRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
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
            'is_published' => ['nullable', 'boolean'],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            // Real HTML is allowed here (sanitized in the controller via
            // HtmlSanitizerService before persisting) — same allow-list
            // reused from the Articles task, no second sanitizer.
            'body' => ['required', 'array'],
            'body.ar' => ['required', 'string', 'max:20000'],
            'body.en' => ['nullable', 'string', 'max:20000'],

            // meta_title/meta_description are NOT nullable columns on the
            // pages table, so both must always be populated.
            'meta_title' => ['required', 'array'],
            'meta_title.ar' => ['required', 'string', 'max:255'],
            'meta_title.en' => ['nullable', 'string', 'max:255'],

            'meta_description' => ['required', 'array'],
            'meta_description.ar' => ['required', 'string', 'max:500'],
            'meta_description.en' => ['nullable', 'string', 'max:500'],
        ] + $this->seoMetaRules();
    }
}
