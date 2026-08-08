<?php

namespace Database\Seeders;

use App\Models\ConversionEvent;
use App\Models\Lead;
use Illuminate\Database\Seeder;

/**
 * Illustrative starter data so a fresh install shows the conversions UI
 * working rather than an empty state.
 *
 * These are SAMPLE events attached to SAMPLE leads — they are not real
 * revenue. Delete them from the dashboard before the site goes live;
 * leaving them in place would make the dashboard's conversion totals
 * report money that was never earned.
 *
 * Does nothing when no leads exist (a fresh seed has none, since leads
 * come from real public form submissions) — so this seeder creates its
 * own clearly-labelled sample lead to hang the events off.
 */
class ConversionEventSeeder extends Seeder
{
    private const SAMPLE_LEAD_EMAIL = 'sample.lead@example.test';

    public function run(): void
    {
        $lead = $this->sampleLead();

        foreach ($this->events() as $event) {
            ConversionEvent::updateOrCreate(
                [
                    'lead_id' => $lead->getKey(),
                    'event_type' => $event['event_type'],
                ],
                [
                    'value' => $event['value'],
                    'currency' => 'SAR',
                    'notes' => $event['notes'],
                    // occurred_at is NOT NULL with no portable default —
                    // SQLite will reject an insert that omits it.
                    'occurred_at' => now()->subDays($event['days_ago']),
                    'url' => $lead->landing_page_url,
                    'utm_snapshot' => [
                        'source_platform' => $lead->source_platform,
                        'utm_campaign' => $lead->utm_campaign,
                    ],
                ]
            );
        }
    }

    private function sampleLead(): Lead
    {
        return Lead::updateOrCreate(
            ['email' => self::SAMPLE_LEAD_EMAIL],
            [
                'full_name' => 'عميل تجريبي (بيانات عينة)',
                'phone' => '+966500000000',
                'type' => 'consultation',
                'source_platform' => 'google',
                'utm_campaign' => 'sample_campaign',
                'landing_page_url' => '/consultation',
                'consent_given' => true,
                'consented_at' => now()->subDays(30),
            ]
        );
    }

    /**
     * @return array<int, array{event_type: string, value: ?float, notes: string, days_ago: int}>
     */
    private function events(): array
    {
        return [
            [
                'event_type' => 'meeting_booked',
                'value' => null,
                'notes' => 'بيانات عينة — اجتماع تعريفي أولي. (SAMPLE DATA)',
                'days_ago' => 21,
            ],
            [
                'event_type' => 'contract_signed',
                'value' => 18500.00,
                'notes' => 'بيانات عينة — عقد تأسيس شركة ذات مسؤولية محدودة. (SAMPLE DATA)',
                'days_ago' => 7,
            ],
        ];
    }
}
