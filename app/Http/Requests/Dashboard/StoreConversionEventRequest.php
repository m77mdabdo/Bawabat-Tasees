<?php

namespace App\Http\Requests\Dashboard;

use App\Models\ConversionEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversionEventRequest extends FormRequest
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
            'event_type' => ['required', 'string', Rule::in(ConversionEvent::TYPES)],

            // Nullable because a milestone like "qualified" is worth
            // logging without a figure attached.
            'value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],

            // Conversions are recorded after the fact, so a past date is
            // normal — but a future one is always a data-entry mistake.
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper($this->input('currency'))]);
        }
    }
}
