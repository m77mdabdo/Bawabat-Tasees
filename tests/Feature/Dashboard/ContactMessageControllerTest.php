<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class ContactMessageControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function contactMessage(array $overrides = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'type' => 'contact',
            'full_name' => 'سارة العتيبي',
            'email' => 'sara@example.com',
            'phone' => '+966500000001',
            'message' => 'أحتاج استشارة بخصوص تأسيس شركة.',
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Admin gating
    // ---------------------------------------------------------------

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard.contact-messages.index'))->assertRedirect(route('login'));
    }

    public function test_non_admins_are_forbidden(): void
    {
        $user = User::factory()->create();
        $message = $this->contactMessage();

        $this->actingAs($user)->get(route('dashboard.contact-messages.index'))->assertForbidden();
        $this->actingAs($user)->patch(route('dashboard.contact-messages.handled', $message))->assertForbidden();

        $this->assertFalse($message->fresh()->is_handled);
    }

    /**
     * The sidebar links to this name. Replacing the coming-soon closure
     * had to keep it, or every dashboard page would 500 on route().
     */
    public function test_the_sidebar_route_name_still_exists(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('dashboard.contact-messages.index'));

        $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk();
    }

    // ---------------------------------------------------------------
    // Only contact-type leads appear
    // ---------------------------------------------------------------

    public function test_only_contact_type_leads_appear(): void
    {
        $contact = $this->contactMessage(['full_name' => 'Contact Person']);
        $consultation = Lead::factory()->create([
            'type' => 'consultation',
            'full_name' => 'Consultation Person',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->assertSee('Contact Person')
            ->assertDontSee('Consultation Person');

        unset($contact, $consultation);
    }

    public function test_soft_deleted_messages_do_not_appear(): void
    {
        $message = $this->contactMessage(['full_name' => 'Deleted Person']);
        $message->delete();

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->assertDontSee('Deleted Person');
    }

    public function test_the_message_body_is_shown(): void
    {
        $this->contactMessage(['message' => 'Please call me about a branch office.']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->assertSee('Please call me about a branch office.');
    }

    public function test_a_message_with_no_body_shows_a_placeholder_not_a_blank_card(): void
    {
        $this->contactMessage(['message' => null]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->assertSee(__('dashboard.contact_messages.no_message'));
    }

    public function test_it_links_to_the_full_lead_record(): void
    {
        $message = $this->contactMessage();

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->assertSee(route('dashboard.leads.show', $message), escape: false);
    }

    public function test_the_reply_link_is_a_mailto_for_the_sender(): void
    {
        $this->contactMessage(['email' => 'sara@example.com']);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('mailto:sara@example.com?subject=', $html);
    }

    // ---------------------------------------------------------------
    // Handled state
    // ---------------------------------------------------------------

    public function test_messages_start_unhandled(): void
    {
        $this->assertFalse($this->contactMessage()->is_handled);
    }

    public function test_marking_a_message_handled_persists(): void
    {
        $message = $this->contactMessage();

        $this->actingAs($this->makeAdmin())
            ->from(route('dashboard.contact-messages.index'))
            ->patch(route('dashboard.contact-messages.handled', $message), ['unhandle' => '0'])
            ->assertRedirect(route('dashboard.contact-messages.index'))
            ->assertSessionHas('status');

        $this->assertTrue($message->fresh()->is_handled);
    }

    public function test_a_handled_message_can_be_marked_unhandled_again(): void
    {
        $message = $this->contactMessage(['is_handled' => true]);

        $this->actingAs($this->makeAdmin())
            ->from(route('dashboard.contact-messages.index'))
            ->patch(route('dashboard.contact-messages.handled', $message), ['unhandle' => '1']);

        $this->assertFalse($message->fresh()->is_handled);
    }

    /**
     * The route is bound to Lead, not to a contact-only model, so it has
     * to refuse anything the inbox would never show.
     */
    public function test_a_consultation_lead_cannot_be_marked_handled_through_this_route(): void
    {
        $lead = Lead::factory()->create(['type' => 'consultation']);

        $this->actingAs($this->makeAdmin())
            ->patch(route('dashboard.contact-messages.handled', $lead))
            ->assertNotFound();

        $this->assertFalse($lead->fresh()->is_handled);
    }

    /**
     * is_handled is not fillable, so a public contact submission cannot
     * arrive pre-marked as already dealt with.
     */
    public function test_is_handled_is_not_mass_assignable(): void
    {
        $lead = new Lead;
        $lead->fill(['type' => 'contact', 'full_name' => 'X', 'is_handled' => true]);

        $this->assertNotTrue($lead->is_handled);
    }

    // ---------------------------------------------------------------
    // Filtering
    // ---------------------------------------------------------------

    public function test_the_unhandled_filter_hides_handled_messages(): void
    {
        $this->contactMessage(['full_name' => 'Still Waiting', 'is_handled' => false]);
        $this->contactMessage(['full_name' => 'Already Done', 'email' => 'b@example.com', 'is_handled' => true]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index', ['filter' => 'unhandled']))
            ->assertOk()
            ->assertSee('Still Waiting')
            ->assertDontSee('Already Done');
    }

    public function test_the_handled_filter_hides_unhandled_messages(): void
    {
        $this->contactMessage(['full_name' => 'Still Waiting', 'is_handled' => false]);
        $this->contactMessage(['full_name' => 'Already Done', 'email' => 'b@example.com', 'is_handled' => true]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index', ['filter' => 'handled']))
            ->assertOk()
            ->assertSee('Already Done')
            ->assertDontSee('Still Waiting');
    }

    public function test_an_unknown_filter_falls_back_to_all(): void
    {
        $this->contactMessage(['full_name' => 'Still Waiting']);
        $this->contactMessage(['full_name' => 'Already Done', 'email' => 'b@example.com', 'is_handled' => true]);

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index', ['filter' => 'nonsense']))
            ->assertOk()
            ->assertSee('Still Waiting')
            ->assertSee('Already Done');

        $this->assertSame('all', $response->viewData('filter'));
    }

    // ---------------------------------------------------------------
    // Sidebar badge
    // ---------------------------------------------------------------

    public function test_the_sidebar_shows_an_unread_count(): void
    {
        $this->contactMessage();
        $this->contactMessage(['email' => 'b@example.com']);
        $this->contactMessage(['email' => 'c@example.com', 'is_handled' => true]);
        Lead::factory()->create(['type' => 'consultation']);

        $html = $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk()->getContent();

        // 2 unhandled contact messages — the handled one and the
        // consultation lead are both excluded.
        $this->assertMatchesRegularExpression('/aria-label="[^"]*"[^>]*>2<\/span>/u', $html);
    }

    public function test_the_badge_disappears_when_nothing_is_unhandled(): void
    {
        $this->contactMessage(['is_handled' => true]);

        $html = $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'aria-label="'.__('dashboard.contact_messages.unhandled').'"',
            $html,
            'A zero badge should not render at all.'
        );
    }

    public function test_the_badge_reflects_a_message_being_handled(): void
    {
        $message = $this->contactMessage();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('aria-label="'.__('dashboard.contact_messages.unhandled').'"', escape: false);

        $this->actingAs($admin)
            ->from(route('dashboard.contact-messages.index'))
            ->patch(route('dashboard.contact-messages.handled', $message), ['unhandle' => '0']);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertDontSee('aria-label="'.__('dashboard.contact_messages.unhandled').'"', escape: false);
    }

    // ---------------------------------------------------------------
    // No new table
    // ---------------------------------------------------------------

    public function test_contact_messages_are_leads_not_a_separate_table(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasTable('contact_messages'),
            'Contact submissions live in leads with type = contact.'
        );

        // And the public form still writes one that this inbox picks up.
        $this->post('/contact', [
            'full_name' => 'زائر',
            'email' => 'visitor@example.com',
            'phone' => '0500000000',
            'message' => 'رسالة من النموذج العام.',
            'consent_given' => '1',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.contact-messages.index'))
            ->assertOk()
            ->assertSee('رسالة من النموذج العام.', escape: false);
    }
}
