<?php

namespace App\Listeners;

use App\Events\LeadSubmitted;
use App\Mail\LeadConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** "We received your request" to the client, in their language. A failure is recorded, never thrown. */
final class EmailClientConfirmation
{
    public function handle(LeadSubmitted $event): void
    {
        $lead = $event->lead;

        if (! config('notifications.confirm_to_client') || ! filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($lead->email, $lead->full_name)->send(new LeadConfirmation($lead));
            $lead->forceFill(['client_confirmed_at' => now()])->saveQuietly();
        } catch (Throwable $e) {
            $lead->recordMailError('client: '.$e->getMessage());
            Log::warning('Lead client confirmation failed', ['lead' => $lead->getKey(), 'error' => $e->getMessage()]);
        }
    }
}
