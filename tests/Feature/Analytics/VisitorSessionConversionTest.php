<?php

namespace Tests\Feature\Analytics;

use App\Models\Article;
use App\Models\Lead;
use App\Models\Service;
use App\Models\VisitorSession;
use App\Services\Analytics\VisitorSessionService;
use App\Services\Analytics\VisitorTrackingGate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Form submissions as action events, and the lead link that turns a journey
 * into a converted one.
 */
class VisitorSessionConversionTest extends TestCase
{
    use RefreshDatabase;

    private const HUMAN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function journey(): VisitorSession
    {
        return VisitorSession::factory()->create([
            'events' => [[
                'type' => VisitorSession::TYPE_PAGEVIEW, 'url' => '/consultation', 'label' => null,
                'ts' => now()->toIso8601String(), 'duration_ms' => null,
            ]],
            'page_count' => 1,
        ]);
    }

    private function submit(string $uri, array $payload, ?string $uid = null, array $headers = [], array $cookies = []): TestResponse
    {
        $test = $this->withUnencryptedCookies(array_merge(
            [VisitorTrackingGate::CONSENT_COOKIE => VisitorTrackingGate::CONSENT_GRANTED],
            $cookies
        ))->withHeaders(array_merge(['User-Agent' => self::HUMAN_UA], $headers));

        if ($uid !== null) {
            $test = $test->withCookie(VisitorSessionService::COOKIE, $uid);
        }

        return $test->post($uri, $payload);
    }

    private function consultationPayload(): array
    {
        return [
            'full_name' => 'Sara Q',
            'phone' => '+966500000000',
            'email' => 'sara@example.test',
            'requested_service_id' => Service::first()?->id,
            'consent_given' => '1',
        ];
    }

    private function contactPayload(): array
    {
        return [
            'full_name' => 'Sara Q',
            'email' => 'sara@example.test',
            'phone' => '+966500000000',
            'message' => 'Hello there',
            'consent_given' => '1',
        ];
    }

    // -----------------------------------------------------------------
    // Action events
    // -----------------------------------------------------------------

    public function test_a_consultation_is_logged_as_an_action_and_links_the_lead(): void
    {
        $session = $this->journey();

        $this->submit(route('consultation.store'), $this->consultationPayload(), $session->session_uid)
            ->assertRedirect();

        $session->refresh();
        $lead = Lead::where('type', 'consultation')->sole();

        $this->assertSame($lead->id, $session->lead_id);
        $this->assertTrue($session->hasConverted());

        $events = $session->events;
        $action = end($events);
        $this->assertSame(VisitorSession::TYPE_ACTION, $action['type']);
        $this->assertSame('consultation_submitted', $action['label']);
    }

    public function test_a_contact_submission_is_logged_and_links_the_lead(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), $this->contactPayload(), $session->session_uid)
            ->assertRedirect();

        $session->refresh();

        $events = $session->events;

        $this->assertSame(Lead::where('type', 'contact')->sole()->id, $session->lead_id);
        $this->assertSame('contact_submitted', end($events)['label']);
    }

    /**
     * A comment is engagement, not a conversion — the journey records that it
     * happened without claiming the session produced a lead.
     */
    public function test_a_comment_is_logged_as_an_action_but_does_not_convert_the_session(): void
    {
        $article = Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);
        $session = $this->journey();

        $this->submit(route('articles.comments.store', $article), [
            'name' => 'Sara',
            'email' => 'sara@example.test',
            'body' => 'Useful article, thank you.',
        ], $session->session_uid)->assertRedirect();

        $session->refresh();

        $events = $session->events;

        $this->assertNull($session->lead_id, 'A comment is not a conversion.');
        $this->assertFalse($session->hasConverted());
        $this->assertSame('comment_submitted', end($events)['label']);
    }

    /**
     * An action is not a page — page_count has to stay a count of pages or
     * the "pages per session" column stops meaning anything.
     */
    public function test_an_action_does_not_increment_the_page_count(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), $this->contactPayload(), $session->session_uid)->assertRedirect();

        $session->refresh();

        $this->assertSame(1, $session->page_count);
        $this->assertCount(2, $session->events, 'The action is in the journey, just not counted as a page.');
    }

    // -----------------------------------------------------------------
    // Linking rules
    // -----------------------------------------------------------------

    /**
     * A visitor who submits twice keeps the first link. The journey belongs
     * to the conversion it produced, and repointing it would lose that.
     */
    public function test_a_second_submission_does_not_repoint_the_link(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), $this->contactPayload(), $session->session_uid)->assertRedirect();
        $first = $session->fresh()->lead_id;

        $this->submit(route('contact.store'), array_merge($this->contactPayload(), ['email' => 'other@example.test']), $session->session_uid)
            ->assertRedirect();

        $this->assertSame($first, $session->fresh()->lead_id);
        $this->assertSame(2, Lead::where('type', 'contact')->count(), 'Both leads are still created.');
    }

    public function test_archiving_a_lead_leaves_the_journey_intact(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), $this->contactPayload(), $session->session_uid)->assertRedirect();

        Lead::where('type', 'contact')->sole()->forceDelete();

        $session->refresh();

        $this->assertNull($session->lead_id, 'nullOnDelete, not cascade.');
        $this->assertNotEmpty($session->events, 'The journey that produced the lead survives it.');
    }

    public function test_the_lead_can_reach_its_journeys(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), $this->contactPayload(), $session->session_uid)->assertRedirect();

        $lead = Lead::where('type', 'contact')->sole();

        $this->assertTrue($lead->visitorSessions->contains('id', $session->id));
    }

    // -----------------------------------------------------------------
    // The gate applies here too
    // -----------------------------------------------------------------

    public function test_no_action_is_recorded_without_consent(): void
    {
        $session = $this->journey();

        $this->withHeaders(['User-Agent' => self::HUMAN_UA])
            ->withCookie(VisitorSessionService::COOKIE, $session->session_uid)
            ->post(route('contact.store'), $this->contactPayload())
            ->assertRedirect();

        $session->refresh();

        $this->assertNull($session->lead_id);
        $this->assertCount(1, $session->events);
        $this->assertSame(1, Lead::where('type', 'contact')->count(), 'The form still works — only the tracking is skipped.');
    }

    public function test_no_action_is_recorded_under_do_not_track(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), $this->contactPayload(), $session->session_uid, ['DNT' => '1'])
            ->assertRedirect();

        $this->assertNull($session->fresh()->lead_id);
    }

    /**
     * A visitor whose very first request is the POST itself has no journey —
     * the middleware only ever saw GETs. That must not error.
     */
    public function test_a_submission_with_no_journey_is_harmless(): void
    {
        $this->submit(route('contact.store'), $this->contactPayload())->assertRedirect();

        $this->assertSame(1, Lead::where('type', 'contact')->count());
        $this->assertSame(0, VisitorSession::count());
    }

    /**
     * The honeypot returns before the Lead is created, so a caught bot must
     * leave no journey entry either.
     */
    public function test_a_honeypot_submission_records_nothing(): void
    {
        $session = $this->journey();

        $this->submit(route('contact.store'), array_merge($this->contactPayload(), [
            'website_url' => 'http://spam.test',
        ]), $session->session_uid)->assertRedirect();

        $session->refresh();

        $this->assertSame(0, Lead::where('type', 'contact')->count());
        $this->assertCount(1, $session->events);
        $this->assertNull($session->lead_id);
    }
}
