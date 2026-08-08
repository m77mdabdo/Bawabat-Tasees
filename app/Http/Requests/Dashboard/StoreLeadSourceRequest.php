<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadSourceRequest extends FormRequest
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
            // Matched case-insensitively against utm_source, so the key is
            // restricted to a slug shape and lowercased before saving —
            // "Google Ads" would never match an incoming utm_source.
            'key' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/i',
                Rule::unique('lead_sources', 'key'),
            ],
            'label' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalised BEFORE the unique check so "Google" cannot slip past
        // an existing "google" on a case-sensitive driver.
        if (is_string($this->input('key'))) {
            $this->merge(['key' => strtolower(trim($this->input('key')))]);
        }
    }

    public function messages(): array
    {
        return ['key.regex' => __('dashboard.lead_sources.validation.key_format')];
    }
}
