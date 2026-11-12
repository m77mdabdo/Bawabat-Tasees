<?php

namespace App\Listeners;

use App\Events\LeadSubmitted;
use App\Leads\MailFailure;
use App\Leads\RecipientDirectory;
use App\Mail\NewLeadAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * One separate email per recipient (nobody sees the others' addresses). A failing recipient is
 * recorded on the lead and logged by id and error class, without its address; it never stops
 * the other recipients or loses the lead.
 */
final class EmailTeamAboutLead
{
    public function __construct(private readonly RecipientDirectory $directory) {}

    public function handle(LeadSubmitted $event): void
    {
        $lead = $event->lead;
        $locale = config('notifications.staff_locale', 'ar');
        $sent = 0;
        $errors = [];

        foreach ($this->directory->forLead($lead) as $recipient) {
            try {
                Mail::to($recipient->email, $recipient->name)->send(new NewLeadAlert($lead, $locale));
                $sent++;
                if ($recipient->exists) {
                    $recipient->forceFill(['last_notified_at' => now()])->saveQuietly();
                }
            } catch (Throwable $e) {
                // Recipient id (or "fallback" for the site address), never the address itself.
                $errors[] = 'recipient '.($recipient->exists ? '#'.$recipient->getKey() : 'fallback').': '.MailFailure::describe($e);
            }
        }

        if ($sent > 0) {
            $lead->forceFill(['team_notified_at' => now()])->saveQuietly();
        }
        if ($errors) {
            $lead->recordMailError(implode(' | ', $errors));
            Log::warning('Lead team alert incomplete', ['lead' => $lead->getKey(), 'errors' => $errors]);
        }
    }
}
