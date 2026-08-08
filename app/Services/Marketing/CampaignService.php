<?php

namespace App\Services\Marketing;

use App\Models\Campaign;
use App\Models\Lead;

/**
 * Campaign persistence plus the one piece of logic worth centralising:
 * relinking existing leads when a campaign's external id changes.
 */
class CampaignService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Campaign
    {
        $campaign = Campaign::create($this->normalise($data));

        $this->relinkLeads($campaign);

        return $campaign;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        $previousExternalId = $campaign->external_campaign_id;

        $campaign->update($this->normalise($data));

        if ($campaign->external_campaign_id !== $previousExternalId) {
            $this->unlinkLeads($campaign);
            $this->relinkLeads($campaign);
        }

        return $campaign;
    }

    /**
     * leads.linked_campaign_id is nullOnDelete, so historical leads
     * survive — they simply lose the link, keeping their raw campaign_id
     * string intact.
     */
    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }

    /**
     * Attaches every existing lead whose raw external campaign_id (or
     * utm_campaign) matches this campaign. Leads captured BEFORE the
     * campaign record existed would otherwise stay unlinked forever,
     * since AttributionService only resolves at submission time.
     */
    public function relinkLeads(Campaign $campaign): int
    {
        $externalId = $campaign->external_campaign_id;

        if (! $externalId) {
            return 0;
        }

        return Lead::query()
            ->whereNull('linked_campaign_id')
            ->where(fn ($query) => $query
                ->where('campaign_id', $externalId)
                ->orWhere('utm_campaign', $externalId))
            ->update([
                'linked_campaign_id' => $campaign->getKey(),
                'campaign_name' => $campaign->name,
            ]);
    }

    public function unlinkLeads(Campaign $campaign): int
    {
        return Lead::where('linked_campaign_id', $campaign->getKey())
            ->update(['linked_campaign_id' => null]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['currency'] = $data['currency'] ?? 'SAR';

        foreach (['external_campaign_id', 'notes', 'platform'] as $key) {
            if (isset($data[$key]) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
