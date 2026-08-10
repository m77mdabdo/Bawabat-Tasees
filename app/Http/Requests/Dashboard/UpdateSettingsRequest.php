<?php

namespace App\Http\Requests\Dashboard;

use App\Services\Cms\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
     * Rules are derived from SettingsService::DEFINITION, so a key added
     * there is validated automatically and a key that is NOT there has no
     * rule — and is therefore dropped by validated(), which is the second
     * half of the arbitrary-key protection (the service iterates the
     * definition rather than the payload).
     */
    public function rules(): array
    {
        $rules = ['settings' => ['required', 'array']];

        foreach (SettingsService::keys() as $key => $type) {
            $field = "settings.{$key}";

            $rules += match ($type) {
                SettingsService::TYPE_TRANSLATED => [
                    $field => ['nullable', 'array'],
                    // Arabic-primary, matching the convention used by every
                    // translatable model field in this project.
                    "{$field}.ar" => ['nullable', 'required_with:'.$field.'.en', 'string', 'max:255'],
                    "{$field}.en" => ['nullable', 'string', 'max:255'],
                ],
                SettingsService::TYPE_EMAIL => [$field => ['nullable', 'string', 'email', 'max:255']],
                // Same permissive shape the public consultation/contact
                // forms use — international numbers vary too much to pin
                // down further.
                SettingsService::TYPE_PHONE => [$field => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,30}$/']],
                SettingsService::TYPE_URL => [$field => ['nullable', 'string', 'url', 'max:255', 'starts_with:https://,http://']],
                SettingsService::TYPE_IMAGE => [],
                default => [$field => ['nullable', 'string', 'max:500']],
            };
        }

        /*
         * The logo arrives as a file, outside the settings array.
         *
         * NO SVG, deliberately. The form used to offer it — `accept` listed
         * image/svg+xml and this rule listed `svg` — but Laravel's `image`
         * rule rejects SVG unless given `allow_svg`, and it runs first, so
         * an admin who picked one got a confusing failure with no
         * explanation. Rather than add `allow_svg`, this closes the door:
         * an SVG is an executable document, `storage/settings/*.svg` is
         * served same-origin, and navigating straight to it would run any
         * script it carries in this site's origin. There is no SVG
         * sanitiser in the project, and the media library already restricts
         * itself to raster formats for the same reason.
         *
         * `mimes` is kept alongside `image` so the accepted list is stated
         * once, visibly, rather than inherited from the framework's default.
         */
        $rules['site_logo'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'];

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach (array_keys(SettingsService::keys()) as $key) {
            $attributes["settings.{$key}"] = __('dashboard.settings.fields.'.$key);
            $attributes["settings.{$key}.ar"] = __('dashboard.settings.fields.'.$key).' ('.__('dashboard.common.name_ar').')';
            $attributes["settings.{$key}.en"] = __('dashboard.settings.fields.'.$key).' ('.__('dashboard.common.name_en').')';
        }

        return $attributes;
    }
}
