<?php

namespace App\Mail;

use App\Mail\Concerns\RendersBrandedMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use SensitiveParameter;

/** The dashboard sign-in code, in the staff member's dashboard language. */
class SignInCodeMail extends Mailable
{
    use RendersBrandedMail;

    public function __construct(public User $staff, #[SensitiveParameter] public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.signin.subject', [], $this->staff->dashboardLocale()));
    }

    public function content(): Content
    {
        $locale = $this->staff->dashboardLocale();

        return $this->branded('mail.sign-in-code', $locale, __('mail.signin.subject', [], $locale), __('mail.signin.kicker', [], $locale), [
            'preheader' => __('mail.signin.intro', [], $locale),
            'name' => $this->staff->name,
            'code' => $this->code,
            'minutes' => $this->minutes,
        ]);
    }
}
