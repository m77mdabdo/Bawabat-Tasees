<?php

namespace App\Mail;

use App\Leads\LeadPresenter;
use App\Mail\Concerns\RendersBrandedMail;
use App\Models\Lead;
use App\Support\DashboardLinks;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Instant alert to the team for a new consultation request or contact message. Replying goes to the client. */
class NewLeadAlert extends Mailable
{
    use RendersBrandedMail;

    public function __construct(public Lead $lead, public string $mailLocale = 'ar') {}

    public function envelope(): Envelope
    {
        // Header-safe: a name can never carry a line break into the subject.
        $name = str_replace(["\r", "\n"], ' ', $this->lead->full_name);

        return new Envelope(
            subject: __('mail.lead.team_subject', [
                'kind' => LeadPresenter::kind($this->lead, $this->mailLocale),
                'ref' => $this->lead->reference(),
                'name' => $name,
            ], $this->mailLocale),
            replyTo: $this->lead->email ? [new Address($this->lead->email, $name)] : [],
        );
    }

    public function content(): Content
    {
        $locale = $this->mailLocale;

        return $this->branded('mail.lead-team', $locale, LeadPresenter::kind($this->lead, $locale).' '.$this->lead->reference(), __('mail.lead.team_kicker', [], $locale), [
            'lead' => $this->lead,
            'intro' => __('mail.lead.team_intro', ['kind' => LeadPresenter::kind($this->lead, $locale)], $locale),
            'clientRows' => LeadPresenter::client($this->lead, $locale),
            'requestRows' => LeadPresenter::request($this->lead, $locale),
            'sourceRows' => LeadPresenter::source($this->lead, $locale),
            'dashboardUrl' => DashboardLinks::lead($this->lead),
        ]);
    }
}
