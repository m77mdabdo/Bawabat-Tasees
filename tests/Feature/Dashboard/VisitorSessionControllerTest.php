<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use App\Models\VisitorSession;
use App\Services\Cms\SettingsRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class VisitorSessionControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    // -----------------------------------------------------------------
    // Access
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function routes(): array
    {
        return [
            'index' => ['dashboard.visitor-sessions.index'],
            'export' => ['dashboard.visitor-sessions.export'],
        ];
    }

    #[DataProvider('routes')]
    public function test_a_guest_is_redirected(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('routes')]
    public function test_a_non_admin_is_forbidden(string $route): void
    {
        $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
    }

    public function test_a_non_admin_cannot_reach_a_single_session_or_its_export(): void
    {
        $session = VisitorSession::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.visitor-sessions.show', $session))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard.visitor-sessions.export-one', $session))->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------

    public function test_the_index_lists_sessions_newest_first(): void
    {
        $older = VisitorSession::factory()->startedAt(now()->subDays(3))->create(['entry_url' => '/older-entry']);
        $newer = VisitorSession::factory()->startedAt(now()->subDay())->create(['entry_url' => '/newer-entry']);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index'))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($html, $older->entry_url),
            strpos($html, $newer->entry_url),
            'The most recent session is the one an admin wants first.'
        );
    }

    public function test_the_index_shows_the_columns_that_make_a_session_readable(): void
    {
        VisitorSession::factory()->create([
            'entry_url' => '/why-invest',
            'page_count' => 137,
            'total_duration_seconds' => 187,
        ]);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('/why-invest', $html);
        // Matched inside its own cell rather than as a bare number, which
        // would collide with any digit pair in a timestamp.
        $this->assertMatchesRegularExpression(
            '/data-label="'.preg_quote(__('dashboard.visitor_sessions.pages'), '/').'"[^>]*>\s*137\s*</',
            $html
        );
        $this->assertStringContainsString('3:07', $html, 'Duration is formatted for reading, not printed in seconds.');
        $this->assertStringContainsString('google', $html);
    }

    public function test_the_index_marks_converted_sessions(): void
    {
        VisitorSession::factory()->converted()->create();
        VisitorSession::factory()->create();

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('dashboard.visitor_sessions.converted'), $html);
        $this->assertStringContainsString(__('dashboard.visitor_sessions.not_converted'), $html);
    }

    /**
     * An empty table with no explanation reads as a broken feature.
     */
    public function test_the_index_explains_itself_when_recording_is_off(): void
    {
        Setting::updateOrCreate(
            ['key' => 'visitor_sessions_enabled'],
            ['value' => false, 'group' => 'visitor_sessions']
        );
        app(SettingsRepository::class)->flush();

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index'))
            ->assertOk()
            ->assertSee(__('dashboard.visitor_sessions.disabled_notice'));
    }

    public function test_the_index_states_the_retention_window(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index'))
            ->assertOk()
            ->assertSee(__('dashboard.visitor_sessions.consent_notice', ['days' => 90]));
    }

    // -----------------------------------------------------------------
    // Filters
    // -----------------------------------------------------------------

    public function test_the_conversion_filter_narrows_the_list(): void
    {
        $converted = VisitorSession::factory()->converted()->create(['entry_url' => '/converted-entry']);
        $plain = VisitorSession::factory()->create(['entry_url' => '/plain-entry']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index', ['converted' => 'converted']))
            ->assertOk()
            ->assertSee($converted->entry_url)
            ->assertDontSee($plain->entry_url);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index', ['converted' => 'not_converted']))
            ->assertOk()
            ->assertSee($plain->entry_url)
            ->assertDontSee($converted->entry_url);
    }

    public function test_the_device_filter_narrows_the_list(): void
    {
        $mobile = VisitorSession::factory()->onMobile()->create(['entry_url' => '/mobile-entry']);
        $desktop = VisitorSession::factory()->create(['entry_url' => '/desktop-entry']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index', ['device' => 'mobile']))
            ->assertOk()
            ->assertSee($mobile->entry_url)
            ->assertDontSee($desktop->entry_url);
    }

    public function test_the_source_filter_reads_the_attribution_snapshot(): void
    {
        $google = VisitorSession::factory()->create(['entry_url' => '/from-google']);
        $meta = VisitorSession::factory()->create([
            'entry_url' => '/from-meta',
            'attribution' => ['latest_touch' => ['utm_source' => 'meta']],
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index', ['source' => 'google']))
            ->assertOk()
            ->assertSee($google->entry_url)
            ->assertDontSee($meta->entry_url);
    }

    public function test_the_date_filters_narrow_the_list(): void
    {
        $old = VisitorSession::factory()->startedAt(now()->subDays(30))->create(['entry_url' => '/old-entry']);
        $recent = VisitorSession::factory()->startedAt(now()->subDay())->create(['entry_url' => '/recent-entry']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index', ['date_from' => now()->subDays(7)->toDateString()]))
            ->assertOk()
            ->assertSee($recent->entry_url)
            ->assertDontSee($old->entry_url);
    }

    /**
     * Both enum filters reach a query builder, so neither is passed through
     * from the query string unchecked.
     */
    public function test_an_unknown_filter_value_is_ignored_rather_than_applied(): void
    {
        $session = VisitorSession::factory()->create(['entry_url' => '/still-listed']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.index', [
                'converted' => 'maybe',
                'device' => "' OR 1=1 --",
            ]))
            ->assertOk()
            ->assertSee($session->entry_url);
    }

    // -----------------------------------------------------------------
    // Detail
    // -----------------------------------------------------------------

    public function test_the_detail_page_renders_the_ordered_journey(): void
    {
        $session = VisitorSession::factory()->create([
            'events' => [
                ['type' => 'pageview', 'url' => '/first-step', 'label' => null, 'ts' => now()->subMinutes(5)->toIso8601String(), 'duration_ms' => 4200],
                ['type' => 'pageview', 'url' => '/second-step', 'label' => null, 'ts' => now()->subMinutes(3)->toIso8601String(), 'duration_ms' => null],
                ['type' => 'action', 'url' => '/contact', 'label' => 'contact_submitted', 'ts' => now()->toIso8601String(), 'duration_ms' => null],
            ],
            'page_count' => 3,
        ]);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.show', $session))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(strpos($html, '/second-step'), strpos($html, '/first-step'));
        $this->assertLessThan(strpos($html, 'contact_submitted'), strpos($html, '/second-step'));
        $this->assertStringContainsString('4.2s', $html, 'A reported duration is shown.');
        $this->assertStringContainsString(__('dashboard.visitor_sessions.no_duration'), $html);
    }

    public function test_the_detail_page_says_when_the_journey_is_truncated(): void
    {
        $session = VisitorSession::factory()->truncated()->create();

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.show', $session))
            ->assertOk()
            ->assertSee(__('dashboard.visitor_sessions.journey_truncated', ['limit' => VisitorSession::MAX_EVENTS]));
    }

    public function test_the_detail_page_shows_the_ip_prefix_with_its_caveat(): void
    {
        $session = VisitorSession::factory()->create(['ip_prefix' => '203.0.113.0']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.show', $session))
            ->assertOk()
            ->assertSee('203.0.113.0')
            ->assertSee(__('dashboard.visitor_sessions.ip_prefix_hint'));
    }

    public function test_the_detail_page_links_a_converted_session_to_its_lead(): void
    {
        $lead = Lead::factory()->create(['full_name' => 'Journey Person']);
        $session = VisitorSession::factory()->converted($lead)->create();

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.show', $session))
            ->assertOk()
            ->assertSee('Journey Person')
            ->assertSee(route('dashboard.leads.show', $lead));
    }

    public function test_a_malformed_event_does_not_break_the_timeline(): void
    {
        $session = VisitorSession::factory()->create([
            'events' => [
                ['type' => 'pageview', 'url' => '/good-step', 'label' => null, 'ts' => now()->toIso8601String(), 'duration_ms' => null],
                ['nonsense' => true],
                'not even an array',
            ],
            'page_count' => 1,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.show', $session))
            ->assertOk()
            ->assertSee('/good-step');
    }

    // -----------------------------------------------------------------
    // Export
    // -----------------------------------------------------------------

    public function test_a_single_session_exports_as_a_json_download(): void
    {
        $lead = Lead::factory()->create();
        $session = VisitorSession::factory()->converted($lead)->create();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.export-one', $session))
            ->assertOk();

        $response->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString(
            'attachment; filename="visitor-session-'.$session->id.'-',
            $response->headers->get('Content-Disposition')
        );

        $payload = $response->json();

        $this->assertSame(1, $payload['session_count']);
        $this->assertArrayHasKey('exported_at', $payload);

        $exported = $payload['sessions'][0];

        $this->assertSame($session->id, $exported['id']);
        $this->assertSame($session->entry_url, $exported['entry_url']);
        $this->assertTrue($exported['converted']);
        $this->assertSame($lead->id, $exported['lead']['id']);
        $this->assertSame('desktop', $exported['device']['type']);
        $this->assertSame('203.0.113.0', $exported['location']['ip_prefix']);
        $this->assertFalse($exported['journey_truncated']);
        $this->assertSame(VisitorSession::MAX_EVENTS, $exported['journey_event_limit']);
        $this->assertIsArray($exported['journey']);
    }

    /**
     * The export is about the JOURNEY. Contact details belong to the Leads
     * section — a journey file that quietly carries them is exactly what
     * ends up forwarded outside the business.
     */
    public function test_the_export_carries_no_contact_details(): void
    {
        $lead = Lead::factory()->create([
            'full_name' => 'Private Person',
            'email' => 'private@example.test',
            'phone' => '+966500000000',
        ]);
        $session = VisitorSession::factory()->converted($lead)->create();

        $body = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.export-one', $session))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Private Person', $body);
        $this->assertStringNotContainsString('private@example.test', $body);
        $this->assertStringNotContainsString('+966500000000', $body);
        // The id is there, so a lead can still be looked up.
        $this->assertStringContainsString('"id": '.$lead->id, $body);
    }

    /**
     * The point of the export sharing the index's query: a file that
     * disagreed with the table it came from would be a silent, dangerous bug.
     */
    public function test_a_filtered_export_matches_the_filtered_table(): void
    {
        VisitorSession::factory()->converted()->create(['entry_url' => '/converted-entry']);
        VisitorSession::factory()->create(['entry_url' => '/plain-entry']);

        $payload = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.export', ['converted' => 'converted']))
            ->assertOk()
            ->json();

        $this->assertSame(1, $payload['session_count']);
        $this->assertSame('/converted-entry', $payload['sessions'][0]['entry_url']);
        $this->assertSame('converted', $payload['filters']['converted']);
    }

    public function test_an_unfiltered_export_contains_every_session(): void
    {
        VisitorSession::factory()->count(4)->create();

        $payload = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.export'))
            ->assertOk()
            ->json();

        $this->assertSame(4, $payload['session_count']);
        $this->assertCount(4, $payload['sessions']);
    }

    public function test_the_export_is_readable_rather_than_wire_formatted(): void
    {
        VisitorSession::factory()->create(['entry_url' => '/services']);

        $body = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.export'))
            ->assertOk()
            ->getContent();

        // Pretty printed, and slashes unescaped — a URL written as
        // "\/services" is needlessly hard to read in a file a person opens.
        $this->assertStringContainsString("\n", $body);
        $this->assertStringContainsString('"/services"', $body);
        $this->assertStringNotContainsString('\\/services', $body);
    }

    public function test_the_export_route_is_not_swallowed_by_the_show_route(): void
    {
        // "visitor-sessions/export" must resolve to the export action rather
        // than being read as a session with the slug "export".
        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.visitor-sessions.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    }

    // -----------------------------------------------------------------
    // Sidebar
    // -----------------------------------------------------------------

    public function test_the_sidebar_links_to_the_section(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('dashboard.visitor-sessions.index'))
            ->assertSee(__('dashboard.sidebar.visitor_sessions'));
    }

    public function test_the_section_is_labelled_in_both_dashboard_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $admin = $this->makeAdmin();
            $admin->update(['locale' => $locale]);

            $this->actingAs($admin)
                ->get(route('dashboard.visitor-sessions.index'))
                ->assertOk()
                ->assertSee(__('dashboard.visitor_sessions.title', [], $locale));
        }
    }
}
