<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingSettingsRequest extends FormRequest
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
            'settings' => ['required', 'array'],
            'settings.*.value' => ['nullable', 'string', 'max:255'],
            'settings.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A key can't be switched on without a value to actually inject — an
     * active-but-empty row would render nothing anyway (see
     * tracking-scripts.blade.php), so this catches the mistake at
     * submit time instead of silently doing nothing.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('settings', []) as $key => $row) {
                if (! empty($row['is_active']) && empty($row['value'])) {
                    $validator->errors()->add(
                        "settings.{$key}.value",
                        __('يجب إدخال قيمة قبل تفعيل هذا المعرّف.')
                    );
                }
            }
        });
    }
}
