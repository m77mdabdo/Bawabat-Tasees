<?php

namespace Tests\Feature\Dashboard;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'حملة الربيع',
            'platform' => 'google',
            'external_campaign_id' => 'g-12345',
            'budget' => '20000',
            'spend' => '12000',
            'currency' => 'SAR',
            'starts_on' => now()->subMonth()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'is_active' => '1',
            'notes' => 'ملاحظة',
        ], $overrides);
    }

    private function makeCampaign(array $overrides = []): Campaign
    {
        return Campaign::create(array_merge([
            'name' => 'حملة قائمة',
            'platform' => 'meta',
            'external_campaign_id' => 'm-999',
            'currency' => 'SAR',
            'is_active' => true,
        ], $overrides));
    }

    private function makeLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'full_name' => 'Ahmed Al-Otaibi',
            'phone' => '+966500000000',
            'email' => 'ahmed@example.com',
            'type' => 'consultation',
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // CRUD
    // ---------------------------------------------------------------

    public function test_admin_can_view_the_index(): void
    {
        $admin = $this->makeAdmin();
        $this->makeCampaign();

        $this->actingAs($admin)->get(route('dashboard.campaigns.index'))
            ->assertOk()
            ->assertSee('حملة قائمة', escape: false);
    }

    public function test_index_shows_an_empty_state(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard.campaigns.index'))
            ->assertOk()
            ->assertSee(__('dashboard.campaigns.empty'), escape: false);
    }

    public function test_admin_can_create_a_campaign(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload())
            ->assertRedirect(route('dashboard.campaigns.index'));

        $campaign = Campaign::firstOrFail();
        $this->assertSame('حملة الربيع', $campaign->name);
        $this->assertSame('google', $campaign->platform);
        $this->assertSame('g-12345', $campaign->external_campaign_id);
        $this->assertSame('12000.00', $campaign->spend);
        $this->assertTrue($campaign->is_active);
    }

    public function test_admin_can_update_a_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->makeCampaign();

        $this->actingAs($admin)->put(route('dashboard.campaigns.update', $campaign), $this->payload([
            'name' => 'اسم محدّث',
            'external_campaign_id' => 'm-999',
        ]))->assertRedirect(route('dashboard.campaigns.index'));

        $this->assertSame('اسم محدّث', $campaign->fresh()->name);
    }

    public function test_admin_can_delete_a_campaign(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->makeCampaign();

        $this->actingAs($admin)->delete(route('dashboard.campaigns.destroy', $campaign))
            ->assertRedirect(route('dashboard.campaigns.index'));

        $this->assertDatabaseCount('campaigns', 0);
    }

    /**
     * linked_campaign_id is nullOnDelete — a deleted campaign must not
     * take its historical leads with it.
     */
    public function test_deleting_a_campaign_keeps_its_leads_but_unlinks_them(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->makeCampaign();
        $lead = $this->makeLead(['linked_campaign_id' => $campaign->id]);

        $this->actingAs($admin)->delete(route('dashboard.campaigns.destroy', $campaign))->assertRedirect();

        $this->assertDatabaseCount('leads', 1);
        $this->assertNull($lead->fresh()->linked_campaign_id);
    }

    public function test_show_page_renders_stats_and_linked_leads(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->makeCampaign(['spend' => 1000]);
        $lead = $this->makeLead(['full_name' => 'Linked Lead', 'linked_campaign_id' => $campaign->id]);
        $lead->conversionEvents()->create([
            'event_type' => 'contract_signed',
            'value' => 4000,
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('dashboard.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Linked Lead')
            ->assertSee('4,000.00')
            // ROI = 4000 / 1000
            ->assertSee('4.00');
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_name_is_required(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_platform_must_be_a_known_value(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload(['platform' => 'myspace']))
            ->assertSessionHasErrors('platform');
    }

    /**
     * external_campaign_id is the key AttributionService matches on, so a
     * duplicate would make the lookup ambiguous.
     */
    public function test_external_campaign_id_must_be_unique(): void
    {
        $admin = $this->makeAdmin();
        $this->makeCampaign(['external_campaign_id' => 'dupe']);

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload(['external_campaign_id' => 'dupe']))
            ->assertSessionHasErrors('external_campaign_id');
    }

    public function test_a_campaign_can_keep_its_own_external_id_on_update(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->makeCampaign(['external_campaign_id' => 'keep-me']);

        $this->actingAs($admin)->put(route('dashboard.campaigns.update', $campaign), $this->payload([
            'external_campaign_id' => 'keep-me',
        ]))->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_end_date_must_not_precede_the_start_date(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload([
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-05-01',
        ]))->assertSessionHasErrors('ends_on');
    }

    public function test_negative_spend_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload(['spend' => '-1']))
            ->assertSessionHasErrors('spend');
    }

    // ---------------------------------------------------------------
    // Lead linking
    // ---------------------------------------------------------------

    /**
     * Leads captured BEFORE the campaign record existed must still get
     * linked, since AttributionService only resolves at submission time.
     */
    public function test_creating_a_campaign_backlinks_existing_matching_leads(): void
    {
        $admin = $this->makeAdmin();
        $matching = $this->makeLead(['campaign_id' => 'g-12345']);
        $byUtm = $this->makeLead(['email' => 'b@example.com', 'utm_campaign' => 'g-12345']);
        $unrelated = $this->makeLead(['email' => 'c@example.com', 'campaign_id' => 'other']);

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload())->assertRedirect();

        $campaign = Campaign::firstOrFail();
        $this->assertSame($campaign->id, $matching->fresh()->linked_campaign_id);
        $this->assertSame($campaign->id, $byUtm->fresh()->linked_campaign_id);
        $this->assertNull($unrelated->fresh()->linked_campaign_id);

        // campaign_name was previously never written by anything.
        $this->assertSame('حملة الربيع', $matching->fresh()->campaign_name);
    }

    public function test_changing_the_external_id_relinks_leads(): void
    {
        $admin = $this->makeAdmin();
        $campaign = $this->makeCampaign(['external_campaign_id' => 'old-id']);
        $oldLead = $this->makeLead(['campaign_id' => 'old-id', 'linked_campaign_id' => $campaign->id]);
        $newLead = $this->makeLead(['email' => 'n@example.com', 'campaign_id' => 'new-id']);

        $this->actingAs($admin)->put(route('dashboard.campaigns.update', $campaign), $this->payload([
            'external_campaign_id' => 'new-id',
        ]))->assertRedirect();

        $this->assertNull($oldLead->fresh()->linked_campaign_id);
        $this->assertSame($campaign->id, $newLead->fresh()->linked_campaign_id);
    }

    /**
     * The raw external string must never be overwritten — it is what the
     * ad platform reported, and the attribution flow depends on it.
     */
    public function test_the_raw_campaign_id_string_is_left_untouched(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead(['campaign_id' => 'g-12345']);

        $this->actingAs($admin)->post(route('dashboard.campaigns.store'), $this->payload())->assertRedirect();

        $this->assertSame('g-12345', $lead->fresh()->campaign_id);
    }

    public function test_a_new_lead_self_links_through_attribution(): void
    {
        $campaign = $this->makeCampaign(['external_campaign_id' => 'live-id']);

        $this->post(route('contact.store'), [
            'full_name' => 'Sara',
            'email' => 'sara@example.com',
            'message' => 'مرحبا',
            'consent_given' => '1',
            'website_url' => '',
            'latest_touch_snapshot' => json_encode(['campaign_id' => 'live-id', 'utm_source' => 'google']),
        ])->assertRedirect();

        $lead = Lead::where('email', 'sara@example.com')->firstOrFail();
        $this->assertSame($campaign->id, $lead->linked_campaign_id);
        $this->assertSame('حملة قائمة', $lead->campaign_name);
    }

    public function test_a_lead_for_an_unknown_campaign_is_still_recorded(): void
    {
        $this->post(route('contact.store'), [
            'full_name' => 'Sara',
            'email' => 'sara@example.com',
            'message' => 'مرحبا',
            'consent_given' => '1',
            'website_url' => '',
            'latest_touch_snapshot' => json_encode(['campaign_id' => 'nobody-knows-this']),
        ])->assertRedirect();

        $lead = Lead::where('email', 'sara@example.com')->firstOrFail();
        $this->assertNull($lead->linked_campaign_id);
        $this->assertSame('nobody-knows-this', $lead->campaign_id);
    }

    // ---------------------------------------------------------------
    // Gating
    // ---------------------------------------------------------------

    public function test_guest_is_redirected_from_every_campaign_route(): void
    {
        $campaign = $this->makeCampaign();

        $this->get(route('dashboard.campaigns.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.campaigns.create'))->assertRedirect(route('login'));
        $this->get(route('dashboard.campaigns.show', $campaign))->assertRedirect(route('login'));
        $this->get(route('dashboard.campaigns.edit', $campaign))->assertRedirect(route('login'));
        $this->post(route('dashboard.campaigns.store'), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('dashboard.campaigns.destroy', $campaign))->assertRedirect(route('login'));

        $this->assertDatabaseCount('campaigns', 1);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();
        $campaign = $this->makeCampaign();

        $this->actingAs($user)->get(route('dashboard.campaigns.index'))->assertForbidden();
        $this->actingAs($user)->post(route('dashboard.campaigns.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->delete(route('dashboard.campaigns.destroy', $campaign))->assertForbidden();

        $this->assertDatabaseCount('campaigns', 1);
    }

    /**
     * Regression guard: the sidebar links to every dashboard section by
     * route name, so removing a route without updating the sidebar 500s
     * EVERY dashboard page, not just the one that changed.
     */
    public function test_every_sidebar_route_resolves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.leads.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.campaigns.index'))->assertOk();
    }
}
