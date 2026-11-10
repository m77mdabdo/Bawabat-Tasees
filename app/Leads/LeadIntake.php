<?php

namespace App\Leads;

use App\Events\LeadSubmitted;
use App\Models\Lead;
use App\Services\Analytics\VisitorSessionService;
use App\Services\Marketing\AttributionService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Turns a validated public form into a saved Lead.
 *
 * Both public forms (consultation and contact) come through here, so the rules that
 * every enquiry shares live in one place: attribution, consent stamp, language, the
 * visitor-journey entry and the LeadSubmitted event that drives every notification.
 */
final class LeadIntake
{
    /** Request fields that are processed here and never written to the lead as-is. */
    private const NON_LEAD_FIELDS = [
        'first_touch_snapshot',
        'latest_touch_snapshot',
        'website_url',
        'consent_given',
        'redirect_to',
    ];

    public function __construct(
        private readonly AttributionService $attribution,
        private readonly VisitorSessionService $visitorSessions,
    ) {}

    /**
     * A filled honeypot means a bot filled every visible-looking field. Callers answer
     * exactly as if the submission succeeded, so the bot never learns it was caught.
     */
    public function isSpam(FormRequest $request): bool
    {
        return $request->filled('website_url');
    }

    public function submit(FormRequest $request, string $type): Lead
    {
        $lead = Lead::create([
            ...$request->safe()->except(self::NON_LEAD_FIELDS),
            ...$this->attribution->resolve($request),
            'type' => $type,
            'locale' => app()->getLocale(),
            'consent_given' => true,
            'consented_at' => now(),
        ]);

        // Silent when the visitor never agreed to journey recording; the form works regardless.
        $this->visitorSessions->recordConversion($request, "{$type}_submitted", $lead);

        LeadSubmitted::dispatch($lead);

        return $lead;
    }
}
