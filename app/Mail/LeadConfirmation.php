<?php

namespace App\Mail;

use App\Leads\LeadPresenter;
use App\Mail\Concerns\RendersBrandedMail;
use App\Models\Lead;
use App\Services\Cms\SettingsRepository;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Confirmation to the client that their request arrived, in the language they wrote it in. */
class LeadConfirmation extends Mailable
{
    use RendersBrandedMail;

    public function __construct(public Lead $lead) {}

    private function mailLocale(): string
    {
        return self::supportedLocale($this->lead->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.lead.client_subject', ['ref' => $this->lead->reference()], $this->mailLocale()));
    }

    public function content(): Content
    {
        $locale = $this->mailLocale();
        $whatsapp = app(SettingsRepository::class)->string('contact_whatsapp');

        return $this->branded('mail.lead-client', $locale, __('mail.lead.client_kicker', [], $locale), __('mail.lead.client_kicker', [], $locale), [
            'preheader' => __('mail.lead.client_intro', [], $locale),
            'lead' => $this->lead,
            'greetingName' => LeadPresenter::greetingName($this->lead->full_name),
            'summaryRows' => LeadPresenter::clientSummary($this->lead, $locale),
            'whatsappUrl' => $whatsapp ? 'https://wa.me/'.preg_replace('/\D+/', '', $whatsapp) : null,
        ]);
    }
}
