<?php

namespace App\Http\Requests\Dashboard;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
            'label' => ['required', 'array'],
            'label.ar' => ['required', 'string', 'max:120'],
            'label.en' => ['nullable', 'string', 'max:120'],

            'link_type' => ['required', Rule::in(MenuItem::LINK_TYPES)],

            // The guard rail that makes a broken nav impossible through the
            // UI: only parameter-free public routes are selectable, so a
            // menu entry can never resolve to a route needing a bound model.
            'route_name' => [
                'nullable',
                'required_if:link_type,route',
                Rule::in(array_keys(MenuItem::ROUTE_WHITELIST)),
            ],

            'url' => [
                'nullable',
                'required_if:link_type,url',
                'string',
                'max:255',
                // Absolute URL, or a root-relative path the admin typed.
                'regex:/^(https?:\/\/|\/)/',
            ],

            'target' => ['nullable', Rule::in(MenuItem::TARGETS)],

            // Nesting is capped at one level: a parent must itself be
            // top-level, so a child can never become a grandparent.
            'parent_id' => [
                'nullable',
                Rule::exists('menu_items', 'id')->whereNull('parent_id'),
            ],

            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.regex' => __('dashboard.menu.validation.url_format'),
            'parent_id.exists' => __('dashboard.menu.validation.parent_invalid'),
        ];
    }
}
