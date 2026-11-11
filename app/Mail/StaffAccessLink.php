<?php

namespace App\Mail;

use App\Mail\Concerns\RendersBrandedMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Welcome invitation for a new staff member, or a fresh password link for an existing one.
 * The link expires; no password is ever emailed.
 */
class StaffAccessLink extends Mailable
{
    use RendersBrandedMail;

    public function __construct(public User $staff, public string $url, public bool $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __($this->invitation ? 'mail.staff.invite_subject' : 'mail.staff.link_subject', [], $this->staff->dashboardLocale()));
    }

    public function content(): Content
    {
        $locale = $this->staff->dashboardLocale();
        $role = $this->staff->roleEnum();

        return $this->branded('mail.staff-access', $locale, __('mail.staff.kicker', [], $locale), __('mail.staff.kicker', [], $locale), [
            'name' => $this->staff->name,
            'email' => $this->staff->email,
            'intro' => $this->invitation
                ? __('mail.staff.invite_intro', ['role' => $role ? __($role->label(), [], $locale) : '—'], $locale)
                : __('mail.staff.link_intro', [], $locale),
            'url' => $this->url,
            'minutes' => (int) config('auth.passwords.users.expire', 60),
        ]);
    }
}
