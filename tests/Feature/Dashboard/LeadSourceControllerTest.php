<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\Marketing\AttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class LeadSourceControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'snapchat',
            'label' => 'Snapchat Ads',
            'is_active' => '1',
            'sort_order' => 5,
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Admin gating
    // ---------------------------------------------------------------

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard.lead-sources.index'))->assertRedirect(route('login'));
    }

    public function test_non_admins_are_forbidden_on_every_action(): void
    {
        $user = User::factory()->create();
        $source = LeadSource::factory()->create();

        $this->actingAs($user)->get(route('dashboard.lead-sources.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard.lead-sources.create'))->assertForbidden();
        $this->actingAs($user)->post(route('dashboard.lead-sources.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($user)->get(route('dashboard.lead-sources.edit', $source))->assertForbidden();
        $this->actingAs($user)->put(route('dashboard.lead-sources.update', $source), $this->validPayload())->assertForbidden();
        $this->actingAs($user)->patch(route('dashboard.lead-sources.active', $source))->assertForbidden();
        $this->actingAs($user)->delete(route('dashboard.lead-sources.destroy', $source))->assertForbidden();

        $this->assertDatabaseHas('lead_sources', ['id' => $source->id]);
        $this->assertDatabaseMissing('lead_sources', ['key' => 'snapchat']);
    }

    /**
     * The sidebar links to dashboard.lead-sources.index by name. If
     * swapping the coming-soon closure for the resource had changed that
     * name, every dashboard page would 500 on the route() call.
     */
    public function test_the_sidebar_route_name_still_exists(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('dashboard.lead-sources.index'));

        $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk();
    }

    // ---------------------------------------------------------------
    // CRUD
    // ---------------------------------------------------------------

    public function test_index_lists_sources_with_their_lead_counts(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'label' => 'TikTok']);
        Lead::factory()->count(3)->create(['source_platform' => 'tiktok']);
        Lead::factory()->create(['source_platform' => 'somewhere-else']);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.lead-sources.index'))
            ->assertOk()
            ->assertSee('tiktok')
            ->assertSee('TikTok');

        $counts = $response->viewData('leadCounts');
        $this->assertSame(3, $counts['tiktok']);

        // The unmatched value surfaces separately rather than vanishing.
        $this->assertSame(['somewhere-else' => 1], $response->viewData('unrecognised'));
        unset($source);
    }

    public function test_the_create_and_edit_forms_render(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'label' => 'TikTok']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.lead-sources.create'))
            ->assertOk()
            ->assertSee(__('dashboard.lead_sources.key_hint'));

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.lead-sources.edit', $source))
            ->assertOk()
            ->assertSee('tiktok');
    }

    /**
     * The seeded sources are what AttributionService matches the standard
     * ad platforms against, so the UI has to warn harder before deleting
     * one than it does for a hand-added source.
     */
    public function test_system_sources_get_a_stronger_delete_confirmation(): void
    {
        LeadSource::factory()->create(['key' => 'google', 'is_system' => true]);
        LeadSource::factory()->create(['key' => 'custom', 'is_system' => false]);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.lead-sources.index'))
            ->assertOk()
            ->assertSee(__('dashboard.lead_sources.system_badge'))
            ->getContent();

        $this->assertStringContainsString(e(__('dashboard.lead_sources.confirm_delete_system')), $html);
        $this->assertStringContainsString(e(__('dashboard.lead_sources.confirm_delete')), $html);
    }

    public function test_admin_can_create_a_source(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), $this->validPayload())
            ->assertRedirect(route('dashboard.lead-sources.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('lead_sources', [
            'key' => 'snapchat',
            'label' => 'Snapchat Ads',
            'is_active' => true,
            'sort_order' => 5,
        ]);
    }

    public function test_admin_can_update_a_source(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'label' => 'TikTok']);

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.lead-sources.update', $source), $this->validPayload([
                'key' => 'tiktok',
                'label' => 'TikTok for Business',
            ]))
            ->assertRedirect(route('dashboard.lead-sources.index'));

        $this->assertDatabaseHas('lead_sources', ['id' => $source->id, 'label' => 'TikTok for Business']);
    }

    public function test_admin_can_delete_a_source(): void
    {
        $source = LeadSource::factory()->create();

        $this->actingAs($this->makeAdmin())
            ->delete(route('dashboard.lead-sources.destroy', $source))
            ->assertRedirect(route('dashboard.lead-sources.index'));

        $this->assertDatabaseMissing('lead_sources', ['id' => $source->id]);
    }

    /**
     * Deleting a source must not take its leads with it — the historical
     * record is worth more than the tidy join.
     */
    public function test_deleting_a_source_keeps_its_leads(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok']);
        $lead = Lead::factory()->create(['source_platform' => 'tiktok']);

        $this->actingAs($this->makeAdmin())->delete(route('dashboard.lead-sources.destroy', $source));

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'source_platform' => 'tiktok']);
    }

    public function test_admin_can_toggle_a_source_active(): void
    {
        $source = LeadSource::factory()->create(['is_active' => true]);

        $this->actingAs($this->makeAdmin())->patch(route('dashboard.lead-sources.active', $source));
        $this->assertFalse($source->fresh()->is_active);

        $this->actingAs($this->makeAdmin())->patch(route('dashboard.lead-sources.active', $source));
        $this->assertTrue($source->fresh()->is_active);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_key_and_label_are_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), ['key' => '', 'label' => ''])
            ->assertSessionHasErrors(['key', 'label']);
    }

    public function test_key_must_be_unique(): void
    {
        LeadSource::factory()->create(['key' => 'tiktok']);

        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), $this->validPayload(['key' => 'tiktok']))
            ->assertSessionHasErrors('key');

        $this->assertSame(1, LeadSource::where('key', 'tiktok')->count());
    }

    /**
     * Matching is case-insensitive, so "TikTok" and "tiktok" would be the
     * same source at attribution time. The unique check has to catch that
     * even on SQLite, where the raw index would happily accept both.
     */
    public function test_key_uniqueness_is_case_insensitive(): void
    {
        LeadSource::factory()->create(['key' => 'tiktok']);

        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), $this->validPayload(['key' => 'TikTok']))
            ->assertSessionHasErrors('key');

        $this->assertSame(1, LeadSource::count());
    }

    public function test_a_key_is_stored_lowercased(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), $this->validPayload(['key' => '  SnapChat  ']));

        $this->assertDatabaseHas('lead_sources', ['key' => 'snapchat']);
    }

    public function test_a_key_with_spaces_or_punctuation_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), $this->validPayload(['key' => 'Google Ads!']))
            ->assertSessionHasErrors('key');
    }

    /**
     * Regression: the unique rule has to ignore the row being edited, or
     * saving a source without touching its key fails validation.
     */
    public function test_a_source_can_be_saved_without_changing_its_key(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'label' => 'TikTok']);

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.lead-sources.update', $source), $this->validPayload([
                'key' => 'tiktok',
                'label' => 'Renamed',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $source->fresh()->label);
    }

    public function test_is_system_cannot_be_set_from_a_request(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.lead-sources.store'), $this->validPayload(['is_system' => '1']));

        $this->assertFalse(LeadSource::where('key', 'snapchat')->first()->is_system);
    }

    // ---------------------------------------------------------------
    // Attribution must survive edits
    // ---------------------------------------------------------------

    /**
     * Drives the REAL AttributionService through its public entry point,
     * exactly as a public form submission does, so these assertions
     * cannot drift from the matching logic they are guarding.
     */
    private function resolve(string $utmSource): ?string
    {
        $request = Request::create('/contact', 'POST', [
            'latest_touch_snapshot' => json_encode(['utm_source' => $utmSource]),
        ]);

        return app(AttributionService::class)->resolve($request)['source_platform'];
    }

    public function test_attribution_still_matches_after_a_label_change(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'label' => 'TikTok']);

        $this->actingAs($this->makeAdmin())->put(route('dashboard.lead-sources.update', $source), $this->validPayload([
            'key' => 'tiktok',
            'label' => 'TikTok for Business',
        ]));

        $this->assertSame('tiktok', $this->resolve('TikTok'));
        $this->assertSame('tiktok', $this->resolve('tiktok'));
    }

    /**
     * THE ONE THAT MATTERS. leads.source_platform stores the key as a
     * plain string, so a rename has to cascade or every existing lead
     * silently stops resolving to any source.
     */
    public function test_renaming_a_key_repoints_its_existing_leads(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'label' => 'TikTok']);
        $mine = Lead::factory()->count(2)->create(['source_platform' => 'tiktok']);
        $other = Lead::factory()->create(['source_platform' => 'google']);

        $this->actingAs($this->makeAdmin())->put(route('dashboard.lead-sources.update', $source), $this->validPayload([
            'key' => 'tiktok-ads',
            'label' => 'TikTok',
        ]));

        foreach ($mine as $lead) {
            $this->assertSame('tiktok-ads', $lead->fresh()->source_platform);
        }

        // Other sources are untouched by the cascade.
        $this->assertSame('google', $other->fresh()->source_platform);

        // And the renamed source still resolves — under the new key.
        $this->assertSame('tiktok-ads', $this->resolve('TikTok-Ads'));
    }

    public function test_a_deactivated_source_still_resolves_historic_leads(): void
    {
        $source = LeadSource::factory()->create(['key' => 'tiktok', 'is_active' => true]);
        Lead::factory()->create(['source_platform' => 'tiktok']);

        $this->actingAs($this->makeAdmin())->patch(route('dashboard.lead-sources.active', $source));

        // AttributionService matches on key alone, deliberately: turning a
        // source off should not rewrite history.
        $this->assertSame('tiktok', $this->resolve('tiktok'));
    }
}
