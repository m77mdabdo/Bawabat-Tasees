<?php

namespace App\Services\Marketing;

use App\Models\ConversionEvent;
use App\Models\Lead;

/**
 * Write side of conversion_events. Kept in a service so the
 * occurred_at guarantee and the attribution snapshot live in one place
 * rather than in the controller.
 */
class ConversionEventService
{
    /**
     * @param  array<string, mixed>  $data  Validated input from StoreConversionEventRequest.
     */
    public function log(Lead $lead, array $data): ConversionEvent
    {
        return $lead->conversionEvents()->create([
            'event_type' => $data['event_type'],
            'value' => $data['value'] ?? null,
            'currency' => $data['currency'] ?? 'SAR',
            'notes' => $data['notes'] ?? null,

            // occurred_at is NOT NULL with no portable default: MySQL
            // silently supplies current_timestamp() for the first
            // TIMESTAMP column but SQLite does not, so omitting it fails
            // outright in the test suite. Always set explicitly.
            'occurred_at' => $data['occurred_at'] ?? now(),

            // Copy the lead's own attribution forward so a conversion
            // stays traceable to its campaign even if the lead is later
            // archived or its UTM columns are edited.
            'url' => $lead->landing_page_url,
            'utm_snapshot' => $this->attributionSnapshot($lead),
        ]);
    }

    public function delete(ConversionEvent $conversionEvent): void
    {
        $conversionEvent->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function attributionSnapshot(Lead $lead): ?array
    {
        $snapshot = array_filter([
            'source_platform' => $lead->source_platform,
            'utm_source' => $lead->utm_source,
            'utm_medium' => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'utm_content' => $lead->utm_content,
            'utm_term' => $lead->utm_term,
            'gclid' => $lead->gclid,
            'fbclid' => $lead->fbclid,
            'ttclid' => $lead->ttclid,
        ], fn ($value) => $value !== null && $value !== '');

        return $snapshot === [] ? null : $snapshot;
    }
}
