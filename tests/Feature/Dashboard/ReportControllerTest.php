<?php

namespace Tests\Feature\Dashboard;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\Dashboard\ReportingService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private ReportingService $reporting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reporting = app(ReportingService::class);
    }

    /**
     * A deliberately small, fully known fixture so every aggregate below
     * has an arithmetic answer that can be asserted exactly:
     *
     *   5 leads   - 3 consultation / 2 contact
     *               3 google / 2 meta
     *   2 of them win:  google lead  -> 10,000  (campaign A, spend 5,000)
     *                   meta lead    ->  2,500  (campaign B, spend 5,000)
     *   1 non-won event (meeting_booked, 999) that must NOT count.
     */
    private function seedFixture(): array
    {
        $campaignA = Campaign::create([
            'name' => 'Campaign A', 'platform' => 'google',
            'external_campaign_id' => 'a-1', 'spend' => 5000, 'currency' => 'SAR', 'is_active' => true,
        ]);
        $campaignB = Campaign::create([
            'name' => 'Campaign B', 'platform' => 'meta',
            'external_campaign_id' => 'b-1', 'spend' => 5000, 'currency' => 'SAR', 'is_active' => true,
        ]);

        $make = function (string $email, string $type, string $platform, ?Campaign $campaign) {
            return Lead::create([
                'full_name' => 'Lead '.$email,
                'phone' => '+966500000000',
                'email' => $email,
                'type' => $type,
                'source_platform' => $platform,
                'linked_campaign_id' => $campaign?->getKey(),
            ]);
        };

        $won1 = $make('w1@example.com', 'consultation', 'google', $campaignA);
        $won2 = $make('w2@example.com', 'contact', 'meta', $campaignB);
        $make('l3@example.com', 'consultation', 'google', $campaignA);
        $make('l4@example.com', 'contact', 'meta', null);
        $make('l5@example.com', 'consultation', 'google', null);

        $won1->conversionEvents()->create(['event_type' => 'contract_signed', 'value' => 10000, 'occurred_at' => now()->subDay()]);
        $won2->conversionEvents()->create(['event_type' => 'payment_received', 'value' => 2500, 'occurred_at' => now()->subDay()]);
        // Not a won type — must be excluded from revenue and conversion counts.
        $won1->conversionEvents()->create(['event_type' => 'meeting_booked', 'value' => 999, 'occurred_at' => now()->subDay()]);

        return ['a' => $campaignA, 'b' => $campaignB];
    }

    // ---------------------------------------------------------------
    // Aggregates
    // ---------------------------------------------------------------

    public function test_funnel_computes_totals_rate_and_averages(): void
    {
        $this->seedFixture();

        $funnel = $this->reporting->funnel(30);

        $this->assertSame(5, $funnel['total']);
        $this->assertSame(2, $funnel['converted']);
        $this->assertSame(40.0, $funnel['rate']);       // 2/5
        $this->assertSame(12500.0, $funnel['value']);   // 10000 + 2500, the 999 excluded
        $this->assertSame(6250.0, $funnel['average']);  // 12500 / 2 won events
    }

    public function test_funnel_is_all_zeroes_with_no_data(): void
    {
        $funnel = $this->reporting->funnel(30);

        $this->assertSame(0, $funnel['total']);
        $this->assertSame(0, $funnel['converted']);
        $this->assertSame(0.0, $funnel['rate']);
        $this->assertSame(0.0, $funnel['value']);
        $this->assertSame(0.0, $funnel['average']);
    }

    public function test_leads_by_type_counts_correctly(): void
    {
        $this->seedFixture();

        $byType = $this->reporting->leadsByType(30)->keyBy('label');

        $this->assertSame(3, $byType['consultation']['value']);
        $this->assertSame(2, $byType['contact']['value']);
    }

    public function test_leads_by_source_platform_counts_correctly(): void
    {
        $this->seedFixture();

        $bySource = $this->reporting->leadsBySourcePlatform(30)->keyBy('label');

        $this->assertSame(3, $bySource['google']['value']);
        $this->assertSame(2, $bySource['meta']['value']);
    }

    public function test_leads_by_lead_source_uses_the_human_readable_label(): void
    {
        $this->seedFixture();
        LeadSource::create(['key' => 'google', 'label' => 'Google Ads', 'is_active' => true, 'sort_order' => 0]);

        $labels = $this->reporting->leadsByLeadSource(30)->pluck('label');

        $this->assertTrue($labels->contains('Google Ads'));
        // No lead_sources row for meta, so the raw key is kept.
        $this->assertTrue($labels->contains('meta'));
    }

    public function test_revenue_by_campaign_computes_revenue_and_roi(): void
    {
        $this->seedFixture();

        $rows = $this->reporting->revenueByCampaign(30)->keyBy('label');

        $this->assertSame(10000.0, $rows['Campaign A']['revenue']);
        $this->assertSame(5000.0, $rows['Campaign A']['spend']);
        $this->assertSame(2.0, $rows['Campaign A']['roi']);   // 10000 / 5000
        $this->assertSame(2, $rows['Campaign A']['leads']);

        $this->assertSame(2500.0, $rows['Campaign B']['revenue']);
        $this->assertSame(0.5, $rows['Campaign B']['roi']);   // 2500 / 5000
    }

    public function test_roi_is_null_when_no_spend_is_recorded(): void
    {
        Campaign::create(['name' => 'No Spend', 'external_campaign_id' => 'n-1', 'currency' => 'SAR', 'is_active' => true]);

        $row = $this->reporting->revenueByCampaign(30)->firstWhere('label', 'No Spend');

        $this->assertNull($row['roi']);
    }

    public function test_revenue_by_source_groups_by_platform(): void
    {
        $this->seedFixture();

        $rows = $this->reporting->revenueBySource(30)->keyBy('label');

        $this->assertSame(10000.0, $rows['google']['revenue']);
        $this->assertSame(2500.0, $rows['meta']['revenue']);
    }

    public function test_leads_over_time_is_zero_filled_across_the_range(): void
    {
        $this->seedFixture();

        $series = $this->reporting->leadsOverTime(30);

        $this->assertCount(30, $series);
        $this->assertSame(5, $series->sum('value'));
        $this->assertSame(5, $series->last()['value'], 'All fixture leads were created today.');
    }

    /**
     * Anything outside the offered ranges must fall back rather than
     * letting a URL drive an unbounded date scan.
     */
    public function test_range_is_whitelisted(): void
    {
        $this->assertSame(30, ReportingService::normaliseRange(30));
        $this->assertSame(90, ReportingService::normaliseRange('90'));
        $this->assertSame(365, ReportingService::normaliseRange(365));
        $this->assertSame(30, ReportingService::normaliseRange(9999));
        $this->assertSame(30, ReportingService::normaliseRange('drop table'));
        $this->assertSame(30, ReportingService::normaliseRange(null));
    }

    public function test_events_outside_the_range_are_excluded(): void
    {
        $lead = Lead::create([
            'full_name' => 'Old', 'phone' => '+966500000000',
            'email' => 'old@example.com', 'type' => 'consultation', 'source_platform' => 'google',
        ]);
        $lead->conversionEvents()->create([
            'event_type' => 'contract_signed', 'value' => 999999,
            'occurred_at' => now()->subDays(200),
        ]);

        $this->assertSame(0.0, $this->reporting->funnel(30)['value']);
        $this->assertSame(999999.0, $this->reporting->funnel(365)['value']);
    }

    // ---------------------------------------------------------------
    // Page
    // ---------------------------------------------------------------

    public function test_reports_page_renders_for_an_admin(): void
    {
        $admin = $this->makeAdmin();
        $this->seedFixture();

        $this->actingAs($admin)->get(route('dashboard.reports.index'))
            ->assertOk()
            ->assertSee(__('dashboard.reports.title'), escape: false)
            ->assertSee('12,500.00')   // total value
            ->assertSee('40%')         // conversion rate
            ->assertSee('Campaign A')
            ->assertSee('2.00×');      // ROI
    }

    public function test_reports_page_renders_an_empty_state_with_no_data(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard.reports.index'))
            ->assertOk()
            ->assertSee(__('dashboard.reports.no_data'), escape: false);
    }

    public function test_reports_page_accepts_a_valid_range(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard.reports.index', ['days' => 365]))->assertOk();
    }

    public function test_reports_page_ignores_an_invalid_range(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard.reports.index', ['days' => 'nonsense']))->assertOk();
    }

    // ---------------------------------------------------------------
    // Gating
    // ---------------------------------------------------------------

    public function test_guest_is_redirected(): void
    {
        $this->get(route('dashboard.reports.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.reports.index'))
            ->assertForbidden();
    }

    public function test_demo_seed_gives_reports_something_to_render(): void
    {
        // The default seed intentionally yields zero revenue; demo data
        // is what makes the Reports screen non-empty.
        $this->seed();
        $this->seed(DemoDataSeeder::class);

        $funnel = $this->reporting->funnel(90);

        $this->assertGreaterThan(0, $funnel['total']);
        $this->assertGreaterThan(0, $funnel['value']);
        $this->assertGreaterThan(0, $this->reporting->revenueByCampaign(90)->count());
    }
}
