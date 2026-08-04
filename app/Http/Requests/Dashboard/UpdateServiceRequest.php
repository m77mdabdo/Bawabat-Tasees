<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('services', 'slug')->ignore($this->route('service')),
            ],
            'icon' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'is_flagship' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'summary' => ['required', 'array'],
            'summary.ar' => ['required', 'string', 'max:1000'],
            'summary.en' => ['nullable', 'string', 'max:1000'],

            'body' => ['required', 'array'],
            'body.ar' => ['required', 'string', 'max:20000'],
            'body.en' => ['nullable', 'string', 'max:20000'],

            'requirements' => ['required', 'array'],
            'requirements.ar' => ['required', 'string', 'max:20000'],
            'requirements.en' => ['nullable', 'string', 'max:20000'],

            'process' => ['required', 'array'],
            'process.ar' => ['required', 'string', 'max:20000'],
            'process.en' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
