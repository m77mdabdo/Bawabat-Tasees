<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class LeadControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    /**
     * created_at is intentionally not fillable on Lead (it's not a form
     * field), so a caller-supplied value has to be applied after create()
     * — mass assignment would silently drop it and Eloquent would stamp
     * "now" instead.
     */
    private function makeLead(array $overrides = []): Lead
    {
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $lead = Lead::create(array_merge([
            'full_name' => 'Ahmed Al-Otaibi',
            'phone' => '+966500000000',
            'email' => 'ahmed@example.com',
            'type' => 'consultation',
            'source_platform' => 'facebook',
            'campaign_name' => 'Ramadan Push',
            'consent_given' => true,
            'consented_at' => now(),
        ], $overrides));

        if ($createdAt) {
            $lead->created_at = $createdAt;
            $lead->save();
        }

        return $lead;
    }

    public function test_guest_is_redirected_from_leads_index(): void
    {
        $this->get(route('dashboard.leads.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_leads_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.leads.index'))->assertForbidden();
    }

    public function test_index_lists_leads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLead();

        $response = $this->actingAs($admin)->get(route('dashboard.leads.index'));

        $response->assertOk();
        $response->assertSee('Ahmed Al-Otaibi');
        $response->assertSee('facebook');
    }

    public function test_index_filters_by_type(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLead(['type' => 'consultation', 'full_name' => 'Consultation Lead']);
        $this->makeLead(['type' => 'contact', 'full_name' => 'Contact Lead']);

        $response = $this->actingAs($admin)->get(route('dashboard.leads.index', ['type' => 'contact']));

        $response->assertOk();
        $response->assertSee('Contact Lead');
        $response->assertDontSee('Consultation Lead');
    }

    public function test_index_filters_by_source_platform(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLead(['source_platform' => 'facebook', 'full_name' => 'Facebook Lead']);
        $this->makeLead(['source_platform' => 'google', 'full_name' => 'Google Lead']);

        $response = $this->actingAs($admin)->get(route('dashboard.leads.index', ['source_platform' => 'google']));

        $response->assertOk();
        $response->assertSee('Google Lead');
        $response->assertDontSee('Facebook Lead');
    }

    public function test_index_filters_by_requested_service(): void
    {
        $admin = $this->makeAdmin();
        $service = Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'],
            'is_active' => true,
        ]);
        $other = Service::create([
            'slug' => 'other-service',
            'name' => ['ar' => 'خدمة أخرى'],
            'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'],
            'is_active' => true,
        ]);

        $this->makeLead(['requested_service_id' => $service->id, 'full_name' => 'Wants Formation']);
        $this->makeLead(['requested_service_id' => $other->id, 'full_name' => 'Wants Other']);

        $response = $this->actingAs($admin)->get(route('dashboard.leads.index', ['requested_service_id' => $service->id]));

        $response->assertOk();
        $response->assertSee('Wants Formation');
        $response->assertDontSee('Wants Other');
    }

    public function test_index_filters_by_date_range(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLead(['full_name' => 'Old Lead', 'created_at' => now()->subMonth()]);
        $this->makeLead(['full_name' => 'Recent Lead', 'created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('dashboard.leads.index', [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Recent Lead');
        $response->assertDontSee('Old Lead');
    }

    public function test_show_displays_customer_and_source_data(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead([
            'utm_source' => 'facebook',
            'utm_campaign' => 'ramadan',
            'gclid' => 'abc123',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.leads.show', $lead));

        $response->assertOk();
        $response->assertSee('بيانات العميل');
        $response->assertSee('من أين جاء العميل');
        $response->assertSee('Ahmed Al-Otaibi');
        $response->assertSee('ramadan');
        $response->assertSee('abc123');
    }

    /**
     * Lead messages are free text from the public internet — they must
     * never be rendered as raw HTML anywhere in the dashboard.
     */
    public function test_show_escapes_lead_message_html(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead(['message' => '<script>alert(1)</script>']);

        $response = $this->actingAs($admin)->get(route('dashboard.leads.show', $lead));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_destroy_archives_lead_via_soft_delete(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();

        $response = $this->actingAs($admin)->delete(route('dashboard.leads.destroy', $lead));

        $response->assertRedirect(route('dashboard.leads.index'));
        $this->assertSoftDeleted($lead);
    }

    public function test_archived_lead_no_longer_appears_in_index(): void
    {
        $admin = $this->makeAdmin();
        $lead = $this->makeLead();
        $lead->delete();

        $response = $this->actingAs($admin)->get(route('dashboard.leads.index'));

        $response->assertDontSee($lead->full_name);
    }

    /**
     * A lead is a historical record — soft-deleting a service must not
     * erase which service an existing lead asked about. Without
     * withTrashed() on the relationship this resolves to null and the
     * dashboard renders a blank service.
     */
    public function test_requested_service_still_resolves_after_the_service_is_soft_deleted(): void
    {
        $service = Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'],
            'is_active' => true,
        ]);
        $lead = $this->makeLead(['requested_service_id' => $service->id]);

        $service->delete();
        $this->assertSoftDeleted($service);

        $this->assertNotNull($lead->fresh()->requestedService);
        $this->assertSame('company-formation', $lead->fresh()->requestedService->slug);
    }

    public function test_leads_index_still_shows_the_service_name_after_it_is_soft_deleted(): void
    {
        $admin = $this->makeAdmin();
        $service = Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'],
            'is_active' => true,
        ]);
        $this->makeLead(['requested_service_id' => $service->id]);
        $service->delete();

        $this->actingAs($admin)
            ->get(route('dashboard.leads.index'))
            ->assertOk()
            ->assertSee('تأسيس الشركات', escape: false);
    }

    public function test_lead_show_still_displays_the_service_after_it_is_soft_deleted(): void
    {
        $admin = $this->makeAdmin();
        $service = Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'],
            'is_active' => true,
        ]);
        $lead = $this->makeLead(['requested_service_id' => $service->id]);
        $service->delete();

        $this->actingAs($admin)
            ->get(route('dashboard.leads.show', $lead))
            ->assertOk()
            ->assertSee('تأسيس الشركات', escape: false);
    }
}
