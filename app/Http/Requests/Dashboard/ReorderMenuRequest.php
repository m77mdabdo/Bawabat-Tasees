<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMenuRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],

            // Every id must be a real menu item — otherwise a crafted
            // payload could quietly no-op or touch nothing meaningful.
            'items.*.id' => ['required', Rule::exists('menu_items', 'id')],

            // Parent must exist AND be top-level, keeping nesting capped
            // at one level. MenuService re-checks this too, since the
            // reorder runs in a transaction over the whole payload.
            'items.*.parent_id' => [
                'nullable',
                Rule::exists('menu_items', 'id')->whereNull('parent_id'),
            ],
        ];
    }
}
