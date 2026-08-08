<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', Rule::in(Campaign::PLATFORMS)],

            // Unique because it is the key AttributionService matches an
            // incoming lead against — two campaigns sharing one external
            // id would make that lookup ambiguous.
            'external_campaign_id' => ['nullable', 'string', 'max:255', 'unique:campaigns,external_campaign_id'],

            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'spend' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],

            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],

            'is_active' => ['nullable', 'boolean'],
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
