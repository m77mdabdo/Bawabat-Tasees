<?php

namespace App\Services\Marketing;

use App\Models\LeadSource;
use Illuminate\Http\Request;

class AttributionService
{
    /**
     * Decodes the first_touch_snapshot/latest_touch_snapshot JSON the
     * public forms submit (populated client-side from the
     * bts_first_touch/bts_latest_touch cookies — see resources/js/
     * attribution.js) and builds the full set of Lead attribution
     * attributes.
     *
     * The flat reporting columns (utm_*, campaign_id, adset_id, ad_id,
     * gclid, fbclid, ttclid, landing_page_url, referrer_url) are
     * populated FROM THE LATEST TOUCH snapshot specifically, not
     * first-touch — latest-touch is what immediately preceded this
     * specific conversion, so it's the most relevant single answer to
     * "where did THIS lead come from" for campaign/channel reporting.
     * Both full JSON snapshots are preserved verbatim in first_touch/
     * latest_touch regardless, so first-touch data is never lost — it
     * just isn't what drives the flat columns.
     */
    public function resolve(Request $request): array
    {
        $firstTouch = $this->decodeSnapshot($request->input('first_touch_snapshot'));
        $latestTouch = $this->decodeSnapshot($request->input('latest_touch_snapshot'));

        return [
            'first_touch' => $firstTouch,
            'latest_touch' => $latestTouch,
            'source_platform' => $this->resolveSourcePlatform($latestTouch['utm_source'] ?? null),
            'utm_source' => $latestTouch['utm_source'] ?? null,
            'utm_medium' => $latestTouch['utm_medium'] ?? null,
            'utm_campaign' => $latestTouch['utm_campaign'] ?? null,
            'utm_content' => $latestTouch['utm_content'] ?? null,
            'utm_term' => $latestTouch['utm_term'] ?? null,
            'campaign_id' => $latestTouch['campaign_id'] ?? null,
            'adset_id' => $latestTouch['adset_id'] ?? null,
            'ad_id' => $latestTouch['ad_id'] ?? null,
            'gclid' => $latestTouch['gclid'] ?? null,
            'fbclid' => $latestTouch['fbclid'] ?? null,
            'ttclid' => $latestTouch['ttclid'] ?? null,
            'landing_page_url' => $latestTouch['landing_page'] ?? null,
            'referrer_url' => $latestTouch['referrer'] ?? null,
        ];
    }

    private function decodeSnapshot(?string $json): ?array
    {
        if (! $json) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Matches utm_source against lead_sources.key case-insensitively.
     * Falls back to the raw utm_source value if it doesn't match a known
     * source — an unrecognized platform is still useful data, so this
     * never hard-fails. Compared in PHP rather than a raw SQL LOWER()
     * clause to sidestep any doubt about `key` being a reserved word in
     * some SQL dialects, and the lead_sources table is tiny (~11 rows),
     * so fetching it all is not a real cost.
     */
    private function resolveSourcePlatform(?string $utmSource): ?string
    {
        if (! $utmSource) {
            return null;
        }

        $match = LeadSource::all()->first(
            fn (LeadSource $source) => strtolower($source->key) === strtolower($utmSource)
        );

        return $match?->key ?? $utmSource;
    }
}
