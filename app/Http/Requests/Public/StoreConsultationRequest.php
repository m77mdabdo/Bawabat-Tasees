<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => ['required', 'email', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'country_of_residence' => ['nullable', 'string', 'max:255'],
            'requested_service_id' => [
                'required',
                Rule::exists('services', 'id')->where('is_active', true),
            ],
            'requested_activity' => ['nullable', 'string', 'max:255'],
            'owns_external_company' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:5000'],
            'consent_given' => ['accepted'],

            // Populated client-side from the bts_first_touch/bts_latest_touch
            // cookies (see resources/js/attribution.js) — raw JSON strings,
            // parsed in AttributionService, not mass-assigned directly.
            'first_touch_snapshot' => ['nullable', 'string'],
            'latest_touch_snapshot' => ['nullable', 'string'],

            // website_url is the honeypot field — deliberately NOT
            // validated here. A validation rule (e.g. "prohibited") would
            // return a 422 and tip off the bot that it was caught; the
            // check instead happens as the first thing in the controller,
            // which silently returns the normal success response without
            // writing a Lead row.
        ];
    }
}
