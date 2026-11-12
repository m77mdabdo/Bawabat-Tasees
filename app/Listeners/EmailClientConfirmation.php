<?php

namespace App\Listeners;

use App\Events\LeadSubmitted;
use App\Leads\LeadPresenter;
use App\Leads\MailFailure;
use App\Mail\LeadConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * "We received your request" to the client, in their language. A failure is recorded, never thrown.
 *
 * The address comes from a public form, so this email can reach anyone: it carries no free text the
 * sender wrote (LeadConfirmation), and one address gets at most DAILY_CAP of them a day.
 */
final class EmailClientConfirmation
{
    public const DAILY_CAP = 3;

    public function handle(LeadSubmitted $event): void
    {
        $lead = $event->lead;

        if (! config('notifications.confirm_to_client') || ! filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $key = 'lead-confirmation:'.hash('sha256', mb_strtolower(trim($lead->email)));
        if (RateLimiter::tooManyAttempts($key, self::DAILY_CAP)) {
            Log::info('Lead client confirmation skipped: daily cap for this address reached', ['lead' => $lead->getKey()]);

            return;
        }
        RateLimiter::hit($key, 86400);

        try {
            $name = LeadPresenter::greetingName($lead->full_name);
            Mail::to($lead->email, $name !== '' ? $name : null)->send(new LeadConfirmation($lead));
            $lead->forceFill(['client_confirmed_at' => now()])->saveQuietly();
        } catch (Throwable $e) {
            $error = MailFailure::describe($e);
            $lead->recordMailError('client: '.$error);
            Log::warning('Lead client confirmation failed', ['lead' => $lead->getKey(), 'error' => $error]);
        }
    }
}
