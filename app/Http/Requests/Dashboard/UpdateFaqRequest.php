<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqRequest extends FormRequest
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
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            'question' => ['required', 'array'],
            'question.ar' => ['required', 'string', 'max:500'],
            'question.en' => ['nullable', 'string', 'max:500'],

            'answer' => ['required', 'array'],
            'answer.ar' => ['required', 'string', 'max:5000'],
            'answer.en' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
