<?php

namespace App\Mail;

use App\Mail\Concerns\RendersBrandedMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent by `php artisan mail:test` to prove the mail settings deliver. */
class TestMail extends Mailable
{
    use RendersBrandedMail;

    public function __construct(public string $mailLocale = 'ar') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.test.subject', [], $this->mailLocale));
    }

    public function content(): Content
    {
        $locale = $this->mailLocale;

        return $this->branded('mail.test', $locale, __('mail.test.subject', [], $locale), __('mail.test.kicker', [], $locale), [
            'time' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i'),
        ]);
    }
}
