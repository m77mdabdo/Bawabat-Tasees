<?php

namespace App\Mail;

use App\Mail\Concerns\RendersBrandedMail;
use App\Models\NotificationRecipient;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Welcome to an address just added to the enquiry-alert list: what it will receive and who added it. */
class RecipientAdded extends Mailable
{
    use RendersBrandedMail;

    public function __construct(public NotificationRecipient $recipient, public string $addedBy) {}

    private function mailLocale(): string
    {
        return self::supportedLocale(config('notifications.staff_locale'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.recipient.subject', [], $this->mailLocale()));
    }

    public function content(): Content
    {
        $locale = $this->mailLocale();
        $r = $this->recipient;
        $kinds = match (true) {
            $r->receives_consultations && $r->receives_contact_messages => 'kinds_both',
            $r->receives_contact_messages => 'kinds_contact',
            default => 'kinds_consultation',
        };

        return $this->branded('mail.recipient-added', $locale, __('mail.recipient.subject', [], $locale), __('mail.recipient.kicker', [], $locale), [
            'name' => $r->label(),
            'intro' => __('mail.recipient.intro', [
                'by' => $this->addedBy,
                'email' => $r->email,
                'kinds' => __("mail.recipient.{$kinds}", [], $locale),
            ], $locale),
        ]);
    }
}
