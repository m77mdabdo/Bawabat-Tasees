<?php

namespace Tests\Feature\Dashboard;

use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class ConversionEventControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function makeLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'full_name' => 'Ahmed Al-Otaibi',
            'phone' => '+966500000000',
            'email' => 'ahmed@example.com',
            'type' => 'consultation',
            'source_platform' => 'google',
            'utm_campaign' => 'spring_push',
            'landing_page_url' => '/consultation',
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'event_type' => 'contract_signed',
            'value' => '18500.00',
            'currency' => 'SAR',
            'occurred_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'notes' => 'ملاحظة اختبار',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Logging
    // ---------------------------------------------------------------

    public function test_admin_can_log_a_conversion_against_a_lead(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload())
            ->assertRedirect(route('dashboard.leads.show', $lead));

        $this->assertDatabaseCount('conversion_events', 1);

        $event = ConversionEvent::firstOrFail();
        $this->assertSame($lead->id, $event->lead_id);
        $this->assertSame('contract_signed', $event->event_type);
        $this->assertSame('18500.00', $event->value);
        $this->assertSame('SAR', $event->currency);
        $this->assertSame('ملاحظة اختبار', $event->notes);
    }

    /**
     * occurred_at is NOT NULL with no portable default — SQLite rejects an
     * insert that omits it, so the service must always set it.
     */
    public function test_occurred_at_defaults_to_now_when_omitted(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload(['occurred_at' => null]))
            ->assertRedirect();

        $event = ConversionEvent::firstOrFail();
        $this->assertNotNull($event->occurred_at);
        $this->assertTrue($event->occurred_at->isToday());
    }

    public function test_a_backdated_occurred_at_is_preserved_not_overwritten_with_now(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();
        $when = now()->subDays(10)->startOfMinute();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload([
                'occurred_at' => $when->format('Y-m-d\TH:i'),
            ]))->assertRedirect();

        $this->assertSame(
            $when->format('Y-m-d H:i'),
            ConversionEvent::firstOrFail()->occurred_at->format('Y-m-d H:i')
        );
    }

    public function test_a_value_free_milestone_can_be_logged(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload([
                'event_type' => 'qualified',
                'value' => null,
            ]))->assertRedirect();

        $this->assertNull(ConversionEvent::firstOrFail()->value);
    }

    public function test_the_leads_attribution_is_snapshotted_onto_the_event(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload())
            ->assertRedirect();

        $event = ConversionEvent::firstOrFail();
        $this->assertSame('google', $event->utm_snapshot['source_platform']);
        $this->assertSame('spring_push', $event->utm_snapshot['utm_campaign']);
        $this->assertSame('/consultation', $event->url);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_event_type_is_required_and_must_be_a_known_type(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload(['event_type' => null]))
            ->assertSessionHasErrors('event_type');

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload(['event_type' => 'made_up']))
            ->assertSessionHasErrors('event_type');

        $this->assertDatabaseCount('conversion_events', 0);
    }

    public function test_a_negative_value_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload(['value' => '-5']))
            ->assertSessionHasErrors('value');

        $this->assertDatabaseCount('conversion_events', 0);
    }

    public function test_a_future_occurred_at_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload([
                'occurred_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ]))->assertSessionHasErrors('occurred_at');

        $this->assertDatabaseCount('conversion_events', 0);
    }

    public function test_currency_is_normalised_to_uppercase(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload(['currency' => 'usd']))
            ->assertRedirect();

        $this->assertSame('USD', ConversionEvent::firstOrFail()->currency);
    }

    // ---------------------------------------------------------------
    // Show page rendering
    // ---------------------------------------------------------------

    public function test_show_page_renders_logged_conversions(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();
        $lead->conversionEvents()->create([
            'event_type' => 'contract_signed',
            'value' => 18500,
            'currency' => 'SAR',
            'notes' => 'ملاحظة ظاهرة',
            'occurred_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->get(route('dashboard.leads.show', $lead))
            ->assertOk()
            ->assertSee(__('dashboard.conversions.types.contract_signed'), escape: false)
            ->assertSee('18,500.00')
            ->assertSee('ملاحظة ظاهرة', escape: false)
            ->assertSee(__('dashboard.conversions.converted_badge'), escape: false);
    }

    public function test_show_page_renders_an_empty_state_when_there_are_none(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $this->actingAs($admin)->get(route('dashboard.leads.show', $lead))
            ->assertOk()
            ->assertSee(__('dashboard.conversions.empty'), escape: false)
            ->assertDontSee(__('dashboard.conversions.converted_badge'), escape: false);
    }

    // ---------------------------------------------------------------
    // Deleting
    // ---------------------------------------------------------------

    public function test_admin_can_delete_a_conversion(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();
        $event = $lead->conversionEvents()->create([
            'event_type' => 'qualified',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('dashboard.leads.conversions.destroy', [$lead, $event]))
            ->assertRedirect(route('dashboard.leads.show', $lead));

        $this->assertDatabaseCount('conversion_events', 0);
    }

    /**
     * Nested route binding is not scoped automatically, so deleting via a
     * mismatched lead id must 404 rather than removing another lead's row.
     */
    public function test_deleting_an_event_belonging_to_another_lead_404s(): void
    {
        $admin = $this->makeAdmin();
        $leadA = $this->makeLead();
        $leadB = $this->makeLead(['email' => 'other@example.com']);
        $event = $leadB->conversionEvents()->create([
            'event_type' => 'qualified',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('dashboard.leads.conversions.destroy', [$leadA, $event]))
            ->assertNotFound();

        $this->assertDatabaseCount('conversion_events', 1);
    }

    // ---------------------------------------------------------------
    // Index badge + filter
    // ---------------------------------------------------------------

    public function test_index_shows_the_converted_badge_only_for_converted_leads(): void
    {
        $admin = $this->makeAdmin();
        $converted = $this->makeLead(['full_name' => 'Converted Lead']);
        $converted->conversionEvents()->create(['event_type' => 'payment_received', 'occurred_at' => now()]);
        $this->makeLead(['full_name' => 'Plain Lead', 'email' => 'plain@example.com']);

        $html = $this->actingAs($admin)->get(route('dashboard.leads.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, __('dashboard.conversions.converted_badge')));
    }

    /**
     * A meeting is not a sale — it must not flip the converted badge.
     */
    public function test_a_non_won_event_does_not_mark_a_lead_as_converted(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();
        $lead->conversionEvents()->create(['event_type' => 'meeting_booked', 'occurred_at' => now()]);

        $this->assertFalse($lead->fresh()->isConverted());

        $this->actingAs($admin)->get(route('dashboard.leads.index'))
            ->assertOk()
            ->assertDontSee(__('dashboard.conversions.converted_badge'), escape: false);
    }

    public function test_index_filters_to_converted_leads_only(): void
    {
        $admin = $this->makeAdmin();
        $converted = $this->makeLead(['full_name' => 'Converted Lead']);
        $converted->conversionEvents()->create(['event_type' => 'contract_signed', 'occurred_at' => now()]);
        $this->makeLead(['full_name' => 'Plain Lead', 'email' => 'plain@example.com']);

        $this->actingAs($admin)->get(route('dashboard.leads.index', ['conversion' => 'converted']))
            ->assertOk()
            ->assertSee('Converted Lead')
            ->assertDontSee('Plain Lead');
    }

    public function test_index_filters_to_not_converted_leads_only(): void
    {
        $admin = $this->makeAdmin();
        $converted = $this->makeLead(['full_name' => 'Converted Lead']);
        $converted->conversionEvents()->create(['event_type' => 'contract_signed', 'occurred_at' => now()]);
        $this->makeLead(['full_name' => 'Plain Lead', 'email' => 'plain@example.com']);

        $this->actingAs($admin)->get(route('dashboard.leads.index', ['conversion' => 'not_converted']))
            ->assertOk()
            ->assertSee('Plain Lead')
            ->assertDontSee('Converted Lead');
    }

    // ---------------------------------------------------------------
    // Dashboard tiles
    // ---------------------------------------------------------------

    public function test_dashboard_home_shows_converted_count_and_total_value(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();
        $lead->conversionEvents()->create([
            'event_type' => 'contract_signed',
            'value' => 12000,
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.home.converted_leads'), escape: false)
            ->assertSee(__('dashboard.home.conversion_value'), escape: false)
            ->assertSee('12,000.00');
    }

    // ---------------------------------------------------------------
    // Admin gating
    // ---------------------------------------------------------------

    public function test_guest_cannot_log_or_delete_a_conversion(): void
    {
        $lead = $this->makeLead();
        $event = $lead->conversionEvents()->create(['event_type' => 'qualified', 'occurred_at' => now()]);

        $this->post(route('dashboard.leads.conversions.store', $lead), $this->payload())
            ->assertRedirect(route('login'));
        $this->delete(route('dashboard.leads.conversions.destroy', [$lead, $event]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('conversion_events', 1);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();
        $lead = $this->makeLead();
        $event = $lead->conversionEvents()->create(['event_type' => 'qualified', 'occurred_at' => now()]);

        $this->actingAs($user)
            ->post(route('dashboard.leads.conversions.store', $lead), $this->payload())
            ->assertForbidden();
        $this->actingAs($user)
            ->delete(route('dashboard.leads.conversions.destroy', [$lead, $event]))
            ->assertForbidden();

        $this->assertDatabaseCount('conversion_events', 1);
    }

    // ---------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------

    /**
     * lead_id survives a soft-delete, so the relation must still resolve —
     * matching the Lead::requestedService withTrashed() fix.
     */
    public function test_event_still_resolves_its_lead_after_the_lead_is_archived(): void
    {
        $lead = $this->makeLead();
        $event = $lead->conversionEvents()->create(['event_type' => 'qualified', 'occurred_at' => now()]);

        $lead->delete();
        $this->assertSoftDeleted($lead);

        $fresh = ConversionEvent::findOrFail($event->id);
        $this->assertNull($fresh->lead, 'Plain relation should honour the soft-delete scope.');
        $this->assertNotNull($fresh->leadWithTrashed, 'leadWithTrashed must still resolve.');
        $this->assertSame($lead->id, $fresh->leadWithTrashed->id);
    }

    public function test_demo_seed_has_sample_conversion_events(): void
    {
        // Sample conversions live in DemoDataSeeder now — the default
        // seed deliberately produces none.
        $this->seed();
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThan(0, ConversionEvent::count());
        $this->assertSame(0, ConversionEvent::whereNull('occurred_at')->count());
    }
}
