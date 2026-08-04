<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
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
                Rule::unique('countries', 'slug')->ignore($this->route('country')),
            ],
            'flag_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],

            'notes' => ['nullable', 'array'],
            'notes.ar' => ['nullable', 'string', 'max:2000'],
            'notes.en' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
