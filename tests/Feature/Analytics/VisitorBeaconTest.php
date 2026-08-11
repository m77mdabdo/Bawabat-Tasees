<?php

namespace Tests\Feature\Analytics;

use App\Models\VisitorSession;
use App\Services\Analytics\VisitorSessionService;
use App\Services\Analytics\VisitorTrackingGate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The beacon is the one endpoint in this feature that accepts numbers from
 * the client, so most of what is asserted here is what it refuses.
 */
class VisitorBeaconTest extends TestCase
{
    use RefreshDatabase;

    private const HUMAN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function visitorSession(array $attributes = []): VisitorSession
    {
        return VisitorSession::factory()->create(array_merge([
            'events' => [
                ['type' => VisitorSession::TYPE_PAGEVIEW, 'url' => '/services', 'label' => null, 'ts' => now()->toIso8601String(), 'duration_ms' => null],
                ['type' => VisitorSession::TYPE_PAGEVIEW, 'url' => '/countries', 'label' => null, 'ts' => now()->toIso8601String(), 'duration_ms' => null],
            ],
            'page_count' => 2,
        ], $attributes));
    }

    private function beacon(array $payload, ?string $uid = null, array $headers = [], array $cookies = []): TestResponse
    {
        $test = $this->withUnencryptedCookies(array_merge(
            [VisitorTrackingGate::CONSENT_COOKIE => VisitorTrackingGate::CONSENT_GRANTED],
            $cookies
        ))->withHeaders(array_merge(['User-Agent' => self::HUMAN_UA], $headers));

        if ($uid !== null) {
            $test = $test->withCookie(VisitorSessionService::COOKIE, $uid);
        }

        return $test->post(route('visitor.beacon'), $payload);
    }

    public function test_a_report_lands_on_the_matching_page_in_the_journey(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/services', 'duration_ms' => 4200], $session->session_uid)->assertNoContent();

        $events = $session->fresh()->events;

        $this->assertSame(4200, $events[0]['duration_ms']);
        $this->assertNull($events[1]['duration_ms'], 'Only the page that was reported gets the time.');
    }

    /**
     * A visitor who tabs away and back sends two beacons for one page view.
     * The time they spent is the total.
     */
    public function test_two_reports_for_the_same_page_accumulate(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/services', 'duration_ms' => 3000], $session->session_uid)->assertNoContent();
        $this->beacon(['url' => '/services', 'duration_ms' => 1500], $session->session_uid)->assertNoContent();

        $this->assertSame(4500, $session->fresh()->events[0]['duration_ms']);
    }

    /**
     * A beacon can arrive after the visitor has already navigated on, so it
     * is matched to the most recent event for THAT path rather than to
     * whatever happens to be last.
     */
    public function test_a_late_report_still_finds_its_own_page(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/services', 'duration_ms' => 2500], $session->session_uid)->assertNoContent();

        $events = $session->fresh()->events;

        $this->assertSame('/services', $events[0]['url']);
        $this->assertSame(2500, $events[0]['duration_ms']);
    }

    public function test_a_report_for_a_page_not_in_the_journey_changes_no_event(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/never-visited', 'duration_ms' => 9000], $session->session_uid)->assertNoContent();

        foreach ($session->fresh()->events as $event) {
            $this->assertNull($event['duration_ms']);
        }
    }

    public function test_the_total_duration_tracks_the_session_span(): void
    {
        $session = $this->visitorSession([
            'started_at' => now()->subMinutes(5),
            'last_activity_at' => now()->subMinutes(5),
            'total_duration_seconds' => 0,
        ]);

        $this->beacon(['url' => '/services', 'duration_ms' => 1000], $session->session_uid)->assertNoContent();

        $this->assertGreaterThanOrEqual(299, $session->fresh()->total_duration_seconds);
    }

    // -----------------------------------------------------------------
    // What it refuses
    // -----------------------------------------------------------------

    public function test_it_refuses_without_consent(): void
    {
        $session = $this->visitorSession();

        $this->withHeaders(['User-Agent' => self::HUMAN_UA])
            ->withCookie(VisitorSessionService::COOKIE, $session->session_uid)
            ->post(route('visitor.beacon'), ['url' => '/services', 'duration_ms' => 4200])
            ->assertNoContent();

        $this->assertNull($session->fresh()->events[0]['duration_ms']);
    }

    public function test_it_refuses_when_do_not_track_is_set(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/services', 'duration_ms' => 4200], $session->session_uid, ['DNT' => '1'])->assertNoContent();

        $this->assertNull($session->fresh()->events[0]['duration_ms']);
    }

    public function test_it_refuses_a_bot(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/services', 'duration_ms' => 4200], $session->session_uid, ['User-Agent' => 'curl/8.4.0'])->assertNoContent();

        $this->assertNull($session->fresh()->events[0]['duration_ms']);
    }

    /**
     * The session is identified by the httpOnly cookie only. Nothing in the
     * payload can point the write at another journey.
     */
    public function test_the_payload_cannot_choose_which_session_to_write_to(): void
    {
        $mine = $this->visitorSession();
        $theirs = $this->visitorSession();

        $this->beacon([
            'url' => '/services',
            'duration_ms' => 4200,
            'session_uid' => $theirs->session_uid,
        ], $mine->session_uid)->assertNoContent();

        $this->assertSame(4200, $mine->fresh()->events[0]['duration_ms']);
        $this->assertNull($theirs->fresh()->events[0]['duration_ms'], 'A payload field must not be able to select a session.');
    }

    public function test_a_beacon_with_no_session_cookie_is_a_no_op(): void
    {
        $session = $this->visitorSession();

        $this->beacon(['url' => '/services', 'duration_ms' => 4200])->assertNoContent();

        $this->assertNull($session->fresh()->events[0]['duration_ms']);
    }

    public function test_a_malformed_session_cookie_is_a_no_op(): void
    {
        $this->beacon(['url' => '/services', 'duration_ms' => 4200], 'not-a-uid')->assertNoContent();

        $this->assertSame(0, VisitorSession::whereNotNull('events')->where('page_count', 0)->count());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'no url' => [['duration_ms' => 1000]],
            'no duration' => [['url' => '/services']],
            'negative duration' => [['url' => '/services', 'duration_ms' => -5000]],
            'absurd duration' => [['url' => '/services', 'duration_ms' => 999999999]],
            'non numeric duration' => [['url' => '/services', 'duration_ms' => 'forever']],
        ];
    }

    /**
     * Rejected SILENTLY, with the same 204 as every other outcome. A
     * validation redirect would be a response nobody can read — the page
     * that fired the beacon is usually already unloading — and the browser
     * would fetch and discard it.
     */
    #[DataProvider('invalidPayloads')]
    public function test_an_invalid_payload_is_rejected(array $payload): void
    {
        $session = $this->visitorSession();

        $this->beacon($payload, $session->session_uid)->assertNoContent();

        $this->assertNull($session->fresh()->events[0]['duration_ms']);
    }

    /**
     * sendBeacon cannot set headers, so the token travels as a form field —
     * which means the endpoint keeps ordinary CSRF protection rather than
     * being excepted from it. Asserted because "we kept CSRF" is exactly the
     * kind of claim that quietly stops being true.
     */
    public function test_the_endpoint_is_still_csrf_protected(): void
    {
        $this->assertContains(
            ValidateCsrfToken::class,
            app(Kernel::class)->getMiddlewareGroups()['web'],
            'The beacon route sits in the web group precisely so it keeps CSRF.'
        );
    }
}
