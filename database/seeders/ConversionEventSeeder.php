<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Lead;
use Illuminate\Database\Seeder;

/**
 * FABRICATED leads and conversion revenue. Loaded only via
 * DemoDataSeeder, never by the default DatabaseSeeder.
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
        // Resolved the same way AttributionService would at submission
        // time — seeders write directly, so the link has to be made here.
        $campaign = Campaign::where('external_campaign_id', 'sample_campaign')->first();

        $lead = Lead::updateOrCreate(
            ['email' => self::SAMPLE_LEAD_EMAIL],
            [
                'full_name' => 'عميل تجريبي (بيانات عينة)',
                'phone' => '+966500000000',
                'type' => 'consultation',
                'source_platform' => 'google',
                'campaign_id' => 'sample_campaign',
                'utm_campaign' => 'sample_campaign',
                'linked_campaign_id' => $campaign?->getKey(),
                'campaign_name' => $campaign?->name,
                'landing_page_url' => '/consultation',
                'consent_given' => true,
                'consented_at' => now()->subDays(30),
            ]
        );

        $this->supportingLeads();

        return $lead;
    }

    /**
     * A few extra SAMPLE leads so the Reports charts have more than one
     * bar — spread across platforms, types and dates, one of them on the
     * second sample campaign.
     */
    private function supportingLeads(): void
    {
        $meta = Campaign::where('external_campaign_id', 'sample_meta_retargeting')->first();

        $rows = [
            ['email' => 'sample.lead2@example.test', 'name' => 'عميل تجريبي ٢ (بيانات عينة)', 'platform' => 'meta', 'type' => 'contact', 'days' => 12, 'campaign' => $meta],
            ['email' => 'sample.lead3@example.test', 'name' => 'عميل تجريبي ٣ (بيانات عينة)', 'platform' => 'meta', 'type' => 'consultation', 'days' => 20, 'campaign' => $meta],
            ['email' => 'sample.lead4@example.test', 'name' => 'عميل تجريبي ٤ (بيانات عينة)', 'platform' => 'organic', 'type' => 'contact', 'days' => 5, 'campaign' => null],
            ['email' => 'sample.lead5@example.test', 'name' => 'عميل تجريبي ٥ (بيانات عينة)', 'platform' => 'google', 'type' => 'consultation', 'days' => 2, 'campaign' => null],
        ];

        foreach ($rows as $row) {
            $lead = Lead::updateOrCreate(
                ['email' => $row['email']],
                [
                    'full_name' => $row['name'],
                    'phone' => '+966500000000',
                    'type' => $row['type'],
                    'source_platform' => $row['platform'],
                    'linked_campaign_id' => $row['campaign']?->getKey(),
                    'campaign_name' => $row['campaign']?->name,
                    'consent_given' => true,
                    'consented_at' => now()->subDays($row['days']),
                ]
            );

            // created_at is not fillable (it is not a form field), so it
            // has to be applied after the fact for a realistic spread.
            $lead->created_at = now()->subDays($row['days']);
            $lead->save();
        }

        // One won conversion on the meta campaign so revenue-by-campaign
        // has more than a single row.
        $second = Lead::where('email', 'sample.lead2@example.test')->first();

        if ($second) {
            ConversionEvent::updateOrCreate(
                ['lead_id' => $second->getKey(), 'event_type' => 'payment_received'],
                [
                    'value' => 7400.00,
                    'currency' => 'SAR',
                    'notes' => 'بيانات عينة — دفعة أولى. (SAMPLE DATA)',
                    'occurred_at' => now()->subDays(9),
                ]
            );
        }
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
