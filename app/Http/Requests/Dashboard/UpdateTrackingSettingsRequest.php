<?php

namespace App\Http\Requests\Dashboard;

use App\Models\TrackingSetting;
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

    /**
     * Per-key rules are derived from TrackingSetting::FORMATS, so a key added
     * there is validated automatically and the editor screen, the seeder and
     * the validator cannot drift apart.
     *
     * The generic `settings.*.value` rule stays as the floor for any key not
     * in FORMATS; a key that IS in FORMATS gets the stricter rule, because
     * these values are rendered into inline <script> blocks (see the model).
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array'],
            'settings.*.value' => ['nullable', 'string', 'max:255'],
            'settings.*.is_active' => ['nullable', 'boolean'],
        ];

        foreach (TrackingSetting::FORMATS as $key => $pattern) {
            $rules["settings.{$key}.value"] = ['nullable', 'string', 'max:255', 'regex:'.$pattern];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach (array_keys(TrackingSetting::FORMATS) as $key) {
            $attributes["settings.{$key}.value"] = __('dashboard.tracking_settings.'.$key);
        }

        return $attributes;
    }

    /**
     * The stock :attribute-based regex message says only "the format is
     * invalid", which is useless when the whole problem is not knowing what
     * the format IS. Each message names the platform's own example instead.
     */
    public function messages(): array
    {
        $messages = [];

        foreach (array_keys(TrackingSetting::FORMATS) as $key) {
            $messages["settings.{$key}.value.regex"] = __('dashboard.tracking_settings.validation.format', [
                'attribute' => __('dashboard.tracking_settings.'.$key),
                'example' => __('dashboard.tracking_settings.format_hint.'.$key),
            ]);
        }

        return $messages;
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
                        __('dashboard.tracking_settings.validation.required_when_active')
                    );
                }
            }
        });
    }
}
