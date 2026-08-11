<?php

namespace Tests\Feature\Analytics;

use App\Models\Setting;
use App\Models\VisitorSession;
use App\Services\Analytics\VisitorSessionService;
use App\Services\Analytics\VisitorTrackingGate;
use App\Services\Cms\SettingsRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * The capture path end to end, through real requests.
 *
 * The middleware does its write in terminate(), which Laravel's test client
 * calls for us — so these exercise the same two-phase flow production uses,
 * not a shortcut into the service.
 */
class VisitorSessionCaptureTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private const HUMAN_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * A consenting human. Everything else in this class is a deviation from
     * this baseline.
     */
    private function visit(
        string $uri = '/services',
        array $headers = [],
        array $cookies = [],
        ?string $sessionUid = null,
    ): TestResponse {
        // The consent and touch cookies are in the encryption except-list
        // (they are written by JavaScript), so they travel plain. The
        // session id is NOT — it is server-side only and keeps its
        // encryption, so it has to be sent the way a browser would send it
        // back, encrypted.
        $test = $this->withUnencryptedCookies(array_merge(
            [VisitorTrackingGate::CONSENT_COOKIE => VisitorTrackingGate::CONSENT_GRANTED],
            $cookies
        ))->withHeaders(array_merge(['User-Agent' => self::HUMAN_UA], $headers));

        if ($sessionUid !== null) {
            $test = $test->withCookie(VisitorSessionService::COOKIE, $sessionUid);
        }

        return $test->get($uri);
    }

    // -----------------------------------------------------------------
    // Recording
    // -----------------------------------------------------------------

    public function test_a_consenting_visit_starts_a_session(): void
    {
        $this->visit('/services')->assertOk();

        $session = VisitorSession::sole();

        $this->assertSame('/services', $session->entry_url);
        $this->assertSame(1, $session->page_count);
        $this->assertSame('desktop', $session->device_type);
        $this->assertSame('Chrome', $session->browser);
        $this->assertSame('Windows', $session->platform);
        $this->assertCount(1, $session->events);
        $this->assertSame(VisitorSession::TYPE_PAGEVIEW, $session->events[0]['type']);
        $this->assertSame('/services', $session->events[0]['url']);
    }

    public function test_the_session_cookie_is_issued_and_reused(): void
    {
        $response = $this->visit('/services')->assertOk();

        // Decrypted: this cookie is deliberately NOT in the encryption
        // except-list, so what reaches the browser is ciphertext and what
        // the application sees is the uid.
        $cookie = $response->getCookie(VisitorSessionService::COOKIE);

        $this->assertNotNull($cookie);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly(), 'The session id is server-side only; JS has no reason to read it.');
        $this->assertSame(
            $cookie->getValue(),
            VisitorSession::sole()->session_uid,
            'The cookie has to key the row it just created.'
        );

        // The raw uid must not be readable off the wire.
        $onTheWire = $response->getCookie(VisitorSessionService::COOKIE, false);
        $this->assertNotSame($cookie->getValue(), $onTheWire->getValue());
    }

    public function test_a_second_page_appends_to_the_same_session(): void
    {
        $this->visit('/services')->assertOk();
        $uid = VisitorSession::sole()->session_uid;

        $this->visit('/countries', sessionUid: $uid)->assertOk();

        $session = VisitorSession::sole();

        $this->assertSame(2, $session->page_count);
        $this->assertCount(2, $session->events);
        $this->assertSame('/countries', $session->events[1]['url']);
        // entry_url stays the FIRST page — that is what makes it an entry.
        $this->assertSame('/services', $session->entry_url);
    }

    public function test_a_forged_session_cookie_is_replaced_rather_than_trusted(): void
    {
        $this->visit('/services', sessionUid: 'not-a-real-uid')->assertOk();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', VisitorSession::sole()->session_uid);
    }

    public function test_the_external_referrer_is_kept_and_an_internal_one_is_not(): void
    {
        $this->visit('/services', headers: ['referer' => 'https://www.google.com/search?q=x'])->assertOk();
        $this->assertSame('https://www.google.com/search?q=x', VisitorSession::sole()->referrer);

        VisitorSession::query()->delete();

        $this->visit('/services', headers: ['referer' => url('/countries')])->assertOk();
        $this->assertNull(
            VisitorSession::sole()->referrer,
            'An internal referrer is the previous timeline entry, not a source.'
        );
    }

    /**
     * The attribution snapshot is a COPY of the cookies the forms already
     * submit — the point is that a session reads on its own.
     */
    public function test_the_attribution_snapshot_is_copied_from_the_touch_cookies(): void
    {
        $touch = json_encode(['utm_source' => 'google', 'utm_campaign' => 'brand-search']);

        $this->visit('/services', cookies: [
            'bts_first_touch' => $touch,
            'bts_latest_touch' => $touch,
        ])->assertOk();

        $session = VisitorSession::sole();

        $this->assertSame('google', $session->attribution['latest_touch']['utm_source']);
        $this->assertSame('google', $session->sourcePlatform());
        $this->assertSame('brand-search', $session->campaignName());
    }

    public function test_a_visit_with_no_attribution_cookies_stores_none(): void
    {
        $this->visit('/services')->assertOk();

        $this->assertNull(VisitorSession::sole()->attribution);
    }

    // -----------------------------------------------------------------
    // Privacy: what is NOT stored
    // -----------------------------------------------------------------

    public function test_the_ip_is_stored_only_as_a_truncated_prefix(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])->visit('/services')->assertOk();

        $session = VisitorSession::sole();

        $this->assertSame('203.0.113.0', $session->ip_prefix);
        $this->assertStringNotContainsString('203.0.113.42', json_encode($session->toArray()));
    }

    /**
     * Query strings routinely carry arbitrary user input, and the UTM set is
     * already captured structurally, so nothing that follows a "?" is kept.
     */
    public function test_query_strings_are_stripped_from_every_stored_url(): void
    {
        $this->visit('/services?utm_source=google&email=someone%40example.test')->assertOk();

        $session = VisitorSession::sole();

        $this->assertSame('/services', $session->entry_url);
        $this->assertStringNotContainsString('email', json_encode($session->toArray()));
        $this->assertStringNotContainsString('example.test', json_encode($session->toArray()));
    }

    public function test_the_raw_user_agent_is_never_stored(): void
    {
        $this->visit('/services')->assertOk();

        $this->assertStringNotContainsString('AppleWebKit', json_encode(VisitorSession::sole()->toArray()));
    }

    // -----------------------------------------------------------------
    // The gate
    // -----------------------------------------------------------------

    public function test_nothing_is_recorded_without_consent(): void
    {
        $this->withHeaders(['User-Agent' => self::HUMAN_UA])->get('/services')->assertOk();

        $this->assertSame(0, VisitorSession::count(), 'Consent is opt-in: no cookie means no.');
    }

    public function test_nothing_is_recorded_when_consent_is_refused(): void
    {
        $this->visit('/services', cookies: [VisitorTrackingGate::CONSENT_COOKIE => 'denied'])->assertOk();

        $this->assertSame(0, VisitorSession::count());
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function optOutHeaders(): array
    {
        return [
            'Do Not Track' => [['DNT' => '1']],
            'Global Privacy Control' => [['Sec-GPC' => '1']],
        ];
    }

    #[DataProvider('optOutHeaders')]
    public function test_an_opt_out_header_beats_consent(array $headers): void
    {
        $this->visit('/services', headers: $headers)->assertOk();

        $this->assertSame(
            0,
            VisitorSession::count(),
            'A browser-level refusal outranks a banner the visitor may never have been shown.'
        );
    }

    public function test_dnt_set_to_anything_other_than_one_is_not_an_opt_out(): void
    {
        $this->visit('/services', headers: ['DNT' => '0'])->assertOk();

        $this->assertSame(1, VisitorSession::count());
    }

    #[DataProvider('crawlers')]
    public function test_bots_are_not_recorded(string $userAgent): void
    {
        $this->visit('/services', headers: ['User-Agent' => $userAgent])->assertOk();

        $this->assertSame(0, VisitorSession::count(), "Recorded a bot: {$userAgent}");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function crawlers(): array
    {
        return [
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'facebook preview' => ['facebookexternalhit/1.1'],
            'curl' => ['curl/8.4.0'],
            'headless chrome' => ['Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/120.0.0.0'],
        ];
    }

    public function test_capture_stops_when_an_admin_switches_it_off(): void
    {
        Setting::updateOrCreate(['key' => 'visitor_sessions_enabled'], ['value' => false, 'group' => 'visitor_sessions']);
        app(SettingsRepository::class)->flush();

        $this->visit('/services')->assertOk();

        $this->assertSame(0, VisitorSession::count());
    }

    // -----------------------------------------------------------------
    // What is out of scope
    // -----------------------------------------------------------------

    public function test_the_dashboard_is_not_tracked(): void
    {
        $this->actingAs($this->makeAdmin())
            ->withUnencryptedCookies([VisitorTrackingGate::CONSENT_COOKIE => VisitorTrackingGate::CONSENT_GRANTED])
            ->withHeaders(['User-Agent' => self::HUMAN_UA])
            ->get('/dashboard')
            ->assertOk();

        $this->assertSame(0, VisitorSession::count(), 'Staff behaviour does not belong in a marketing table.');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonPageResponses(): array
    {
        return [
            'sitemap' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
        ];
    }

    #[DataProvider('nonPageResponses')]
    public function test_non_html_responses_are_not_counted_as_pageviews(string $uri): void
    {
        $this->visit($uri)->assertOk();

        $this->assertSame(0, VisitorSession::count());
    }

    public function test_a_404_is_not_counted_as_a_pageview(): void
    {
        $this->visit('/no-such-page')->assertNotFound();

        $this->assertSame(0, VisitorSession::count());
    }

    // -----------------------------------------------------------------
    // The event cap
    // -----------------------------------------------------------------

    public function test_the_stored_journey_stops_at_the_cap_but_the_page_count_does_not(): void
    {
        $session = VisitorSession::factory()->create([
            'events' => array_fill(0, VisitorSession::MAX_EVENTS, [
                'type' => VisitorSession::TYPE_PAGEVIEW, 'url' => '/x', 'label' => null,
                'ts' => now()->toIso8601String(), 'duration_ms' => null,
            ]),
            'page_count' => VisitorSession::MAX_EVENTS,
        ]);

        $this->visit('/countries', sessionUid: $session->session_uid)->assertOk();

        $session->refresh();

        $this->assertCount(VisitorSession::MAX_EVENTS, $session->events, 'The JSON column must not grow past the cap.');
        $this->assertSame(VisitorSession::MAX_EVENTS + 1, $session->page_count);
        $this->assertTrue($session->isTruncated());
    }

    // -----------------------------------------------------------------
    // Cost
    // -----------------------------------------------------------------

    /**
     * The whole design is "one indexed read, one write, after the response
     * has already gone". If this number climbs, that claim is no longer
     * true.
     */
    public function test_capture_costs_at_most_two_queries(): void
    {
        $baseline = $this->countQueries(fn () => $this->withHeaders(['User-Agent' => self::HUMAN_UA])->get('/services'));
        $tracked = $this->countQueries(fn () => $this->visit('/services'));

        $this->assertLessThanOrEqual(
            $baseline + 2,
            $tracked,
            'Capture must cost at most one SELECT plus one INSERT/UPDATE.'
        );
    }

    private function countQueries(\Closure $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $callback();

        return $count;
    }
}
