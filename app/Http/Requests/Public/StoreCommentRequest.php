<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],

            // website_url is the honeypot field — deliberately NOT
            // validated here, same as StoreConsultationRequest/
            // StoreContactRequest. A validation rule would 422 and tip
            // off the bot that it was caught; the check happens first in
            // the controller instead.

            // status is intentionally NOT a validated/accepted input at
            // all — every comment is force-created as 'pending' in the
            // controller regardless of anything submitted, so there is
            // no request field that could ever set a comment to
            // approved on creation.
        ];
    }
}
