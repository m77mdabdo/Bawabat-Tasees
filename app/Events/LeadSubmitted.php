<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A visitor sent a consultation request or a contact message, and it is saved.
 *
 * This is the one place the rest of the system hears about new enquiries: the team
 * alert, the client's confirmation and the dashboard bell are separate listeners
 * (see EventServiceProvider wiring in AppServiceProvider), so a new reaction is a new
 * listener, not an edit to the form controllers.
 */
final class LeadSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Lead $lead) {}
}
