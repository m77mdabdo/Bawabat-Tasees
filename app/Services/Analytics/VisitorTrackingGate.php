<?php

namespace App\Services\Analytics;

use App\Services\Cms\SettingsRepository;
use Illuminate\Http\Request;

/**
 * The single answer to "may we record this request?".
 *
 * Every reason to refuse lives here rather than being spread across the
 * middleware, the beacon endpoint and the form controllers — three call
 * sites that must agree, and would eventually not.
 *
 * The order below is deliberate: the cheapest and most absolute checks
 * first, so a crawler or an opted-out visitor costs a string comparison
 * rather than a settings lookup.
 */
class VisitorTrackingGate
{
    /**
     * Set to "granted" by the consent banner. Any other value, or its
     * absence, means no.
     *
     * Opt-IN, not opt-out: an absent cookie is treated as refusal, so a
     * visitor who never answers the banner is never recorded. That is the
     * only reading of consent that is worth the name.
     */
    public const CONSENT_COOKIE = 'bts_consent';

    public const CONSENT_GRANTED = 'granted';

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly UserAgentParser $userAgents,
    ) {}

    public function allows(Request $request): bool
    {
        return $this->reasonToRefuse($request) === null;
    }

    /**
     * The refusal reason, or null when tracking is allowed. Separate from
     * allows() so tests and the dashboard can say WHY nothing is being
     * recorded, which is otherwise an unpleasant thing to debug.
     */
    public function reasonToRefuse(Request $request): ?string
    {
        // 1. Do Not Track. An explicit browser-level "no" outranks a cookie
        //    banner the visitor may never have been shown, so it is checked
        //    before consent and cannot be overridden by it.
        if ($this->doNotTrackRequested($request)) {
            return 'dnt';
        }

        // 2. Consent. Opt-in — absence is refusal.
        if ($request->cookie(self::CONSENT_COOKIE) !== self::CONSENT_GRANTED) {
            return 'no_consent';
        }

        // 3. Automated traffic. After consent because a bot has no consent
        //    cookie anyway, so this is only reached by something that both
        //    accepted the banner and looks like a crawler — rare, but that
        //    is exactly a headless browser driving the site.
        if ($this->userAgents->isBot($request->userAgent())) {
            return 'bot';
        }

        // 4. The admin switch, last because it is the only check that costs
        //    a query (one, shared with the rest of the request).
        if (! $this->enabled()) {
            return 'disabled';
        }

        return null;
    }

    /**
     * Whether the visitor has agreed to MARKETING tracking — the Meta,
     * TikTok and Google tags.
     *
     * Deliberately NOT allows(). That method also consults
     * visitor_sessions_enabled, which is the switch for journey recording;
     * routing the pixels through it would mean turning off session recording
     * silently killed the marketing tags too, which is not what that switch
     * says it does.
     *
     * What the two share is the part that matters: the same consent cookie
     * and the same DNT/GPC override, so a visitor cannot be refused by one
     * subsystem and tracked by the other.
     */
    public function marketingAllowed(Request $request): bool
    {
        if ($this->doNotTrackRequested($request)) {
            return false;
        }

        return $request->cookie(self::CONSENT_COOKIE) === self::CONSENT_GRANTED;
    }

    /**
     * DNT: 1 is the header. Sec-GPC is the Global Privacy Control signal,
     * which has actual legal force in some jurisdictions where DNT never
     * did; honouring both costs nothing and there is no argument for
     * respecting one and not the other.
     */
    public function doNotTrackRequested(Request $request): bool
    {
        return $request->header('DNT') === '1' || $request->header('Sec-GPC') === '1';
    }

    public function enabled(): bool
    {
        return $this->settings->boolean('visitor_sessions_enabled', true);
    }

    /**
     * How long a session row is kept. Clamped rather than trusted: this
     * value reaches a DELETE, and a retention of 0 or a negative number
     * would mean "delete everything, including today".
     */
    public function retentionDays(): int
    {
        return max(1, min(3650, $this->settings->integer('visitor_sessions_retention_days', 90)));
    }
}
