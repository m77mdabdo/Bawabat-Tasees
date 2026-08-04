<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Open public form — no authentication/authorization gate applies.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'message' => ['required', 'string', 'max:5000'],
            'consent_given' => ['accepted'],

            'first_touch_snapshot' => ['nullable', 'string'],
            'latest_touch_snapshot' => ['nullable', 'string'],

            // website_url honeypot — deliberately unvalidated, see the
            // same note in StoreConsultationRequest.
        ];
    }
}
