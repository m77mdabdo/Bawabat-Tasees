<?php

namespace Tests\Feature\Analytics;

use App\Models\Setting;
use App\Models\TrackingSetting;
use App\Services\Analytics\VisitorTrackingGate;
use App\Services\Cms\SettingsRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The marketing tags must not track anyone before they agree.
 *
 * Two mechanisms, because the platforms differ, and the tests are shaped
 * around that difference:
 *
 *   GOOGLE loads in a Consent Mode v2 "denied" state and is updated to
 *   granted on consent — so the assertion is about the consent SIGNAL and
 *   its position in the document, not about the tag's absence.
 *   META and TIKTOK are not loaded at all until consent — so the assertion
 *   is that nothing invokes the loader.
 */
class MarketingConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->enableAllPixels();
    }

    private function enableAllPixels(): void
    {
        foreach ([
            'meta_pixel_id' => '123456789012345',
            'gtm_container_id' => 'GTM-ABC1234',
            'ga4_measurement_id' => 'G-ABCDE12345',
            'tiktok_pixel_id' => 'C4A1B2C3D4E5F6G7H8I9',
        ] as $key => $value) {
            TrackingSetting::updateOrCreate(['key' => $key], ['value' => $value, 'is_active' => true]);
        }
    }

    private function homepage(array $cookies = [], array $headers = []): string
    {
        return $this->withUnencryptedCookies($cookies)
            ->withHeaders($headers)
            ->get('/')
            ->assertOk()
            ->getContent();
    }

    private function consenting(): string
    {
        return $this->homepage([VisitorTrackingGate::CONSENT_COOKIE => VisitorTrackingGate::CONSENT_GRANTED]);
    }

    // -----------------------------------------------------------------
    // Before consent: nothing tracks
    // -----------------------------------------------------------------

    public function test_meta_and_tiktok_are_not_loaded_before_consent(): void
    {
        $html = $this->homepage();

        // The loader is DEFINED but never invoked, so no request reaches
        // facebook.net or tiktok.com.
        $this->assertStringContainsString('window.btsPixels.load = function', $html);
        $this->assertStringNotContainsString('window.btsPixels.load();', $html);
    }

    public function test_the_meta_noscript_pixel_is_absent_before_consent(): void
    {
        // An <img> fires on parse with no JavaScript involved, so it is the
        // one thing the client-side loader cannot hold back — it has to be
        // gated on the server.
        $this->assertStringNotContainsString('facebook.com/tr?id=', $this->homepage());
    }

    public function test_google_starts_in_a_denied_consent_state(): void
    {
        $html = $this->homepage();

        $this->assertStringContainsString("gtag('consent', 'default'", $html);
        $this->assertStringContainsString("'ad_storage': 'denied'", $html);
        $this->assertStringContainsString("'ad_user_data': 'denied'", $html);
        $this->assertStringContainsString("'ad_personalization': 'denied'", $html);
        $this->assertStringContainsString("'analytics_storage': 'denied'", $html);

        // Not granted anywhere on the page.
        $this->assertStringNotContainsString('window.btsConsent.grantGoogle();', $html);
    }

    /**
     * The ordering IS the mechanism. A consent default that lands after the
     * tag has already initialised is worth nothing, so this is asserted
     * rather than assumed.
     */
    #[DataProvider('googleTags')]
    public function test_the_consent_default_precedes_every_google_tag(string $tagUrl): void
    {
        $html = $this->homepage();

        $default = strpos($html, "gtag('consent', 'default'");
        $tag = strpos($html, $tagUrl);

        $this->assertNotFalse($default);
        $this->assertNotFalse($tag);
        $this->assertLessThan($tag, $default, "The consent default must come before {$tagUrl}.");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function googleTags(): array
    {
        return [
            'gtm container' => ['googletagmanager.com/gtm.js'],
            'gtag loader' => ['googletagmanager.com/gtag/js'],
        ];
    }

    /**
     * The snippets are in the page source either way — the IDs are public
     * and the Tracking Settings screen says so. What matters is that the
     * code sits inside a function nothing has called.
     */
    public function test_the_vendor_snippets_are_inside_the_loader_not_at_the_top_level(): void
    {
        $html = $this->homepage();

        $loaderStart = strpos($html, 'window.btsPixels.load = function');
        $meta = strpos($html, 'connect.facebook.net');
        $tiktok = strpos($html, 'analytics.tiktok.com');

        $this->assertNotFalse($loaderStart);
        $this->assertGreaterThan($loaderStart, $meta, 'The Meta snippet must be inside the deferred loader.');
        $this->assertGreaterThan($loaderStart, $tiktok, 'The TikTok snippet must be inside the deferred loader.');
    }

    // -----------------------------------------------------------------
    // After consent: everything fires
    // -----------------------------------------------------------------

    public function test_meta_and_tiktok_load_once_consent_is_given(): void
    {
        $html = $this->consenting();

        $this->assertStringContainsString('window.btsPixels.load();', $html);
        $this->assertStringContainsString("fbq('init', '123456789012345')", $html);
        $this->assertStringContainsString("ttq.load('C4A1B2C3D4E5F6G7H8I9')", $html);
    }

    public function test_google_is_updated_to_granted_once_consent_is_given(): void
    {
        $html = $this->consenting();

        $this->assertStringContainsString('window.btsConsent.grantGoogle();', $html);
        $this->assertStringContainsString("'ad_storage': 'granted'", $html);
        $this->assertStringContainsString("'analytics_storage': 'granted'", $html);

        // The denied default is still emitted first — granting is an update
        // to it, not a replacement of it.
        $this->assertLessThan(
            strpos($html, 'window.btsConsent.grantGoogle();'),
            strpos($html, "gtag('consent', 'default'")
        );
    }

    public function test_the_meta_noscript_pixel_returns_with_consent(): void
    {
        $this->assertStringContainsString('facebook.com/tr?id=123456789012345', $this->consenting());
    }

    /**
     * Loading twice double-counts every PageView, and both the server block
     * and the banner can call the loader.
     */
    public function test_the_loader_is_guarded_against_double_execution(): void
    {
        $this->assertStringContainsString('if (window.btsPixels.loaded)', $this->homepage());
    }

    // -----------------------------------------------------------------
    // Refusals
    // -----------------------------------------------------------------

    public function test_a_declined_banner_leaves_the_pixels_unloaded(): void
    {
        $html = $this->homepage([VisitorTrackingGate::CONSENT_COOKIE => 'denied']);

        $this->assertStringNotContainsString('window.btsPixels.load();', $html);
        $this->assertStringNotContainsString('window.btsConsent.grantGoogle();', $html);
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
    public function test_an_opt_out_header_beats_a_consent_cookie(array $headers): void
    {
        $html = $this->homepage(
            [VisitorTrackingGate::CONSENT_COOKIE => VisitorTrackingGate::CONSENT_GRANTED],
            $headers
        );

        $this->assertStringNotContainsString('window.btsPixels.load();', $html);
        $this->assertStringNotContainsString('window.btsConsent.grantGoogle();', $html);
        $this->assertStringNotContainsString('facebook.com/tr?id=', $html);
    }

    // -----------------------------------------------------------------
    // Everything else still works
    // -----------------------------------------------------------------

    /**
     * The session-recording switch is not a marketing switch. Turning off
     * journey recording must not silently kill the advertising tags, which
     * is what routing both through the same flag would have done.
     */
    public function test_disabling_session_recording_does_not_disable_the_pixels(): void
    {
        Setting::updateOrCreate(
            ['key' => 'visitor_sessions_enabled'],
            ['value' => false, 'group' => 'visitor_sessions']
        );
        app(SettingsRepository::class)->flush();

        $this->assertStringContainsString('window.btsPixels.load();', $this->consenting());
    }

    public function test_a_platform_that_is_switched_off_renders_nothing_even_with_consent(): void
    {
        TrackingSetting::where('key', 'meta_pixel_id')->update(['is_active' => false]);

        $html = $this->consenting();

        $this->assertStringNotContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString('facebook.com/tr?id=', $html);
        // TikTok is unaffected.
        $this->assertStringContainsString('analytics.tiktok.com', $html);
    }

    public function test_no_tracking_markup_at_all_when_nothing_is_configured(): void
    {
        TrackingSetting::query()->update(['is_active' => false]);

        $html = $this->consenting();

        $this->assertStringNotContainsString('btsPixels', $html);
        $this->assertStringNotContainsString("gtag('consent'", $html);
        $this->assertStringNotContainsString('googletagmanager.com', $html);
    }

    /**
     * A new visitor must be tracked from the page they consented on, not
     * from the next one they happen to load — so the banner releases the
     * tags directly rather than waiting for a navigation.
     */
    public function test_accepting_the_banner_releases_the_tags_without_a_reload(): void
    {
        $js = file_get_contents(resource_path('js/visitor-session.js'));

        $this->assertStringContainsString('function grantMarketingConsent()', $js);
        $this->assertStringContainsString('window.btsConsent?.grantGoogle?.()', $js);
        $this->assertStringContainsString('window.btsPixels?.load?.()', $js);
        // Called from accept(), and nowhere in decline().
        $this->assertMatchesRegularExpression('/accept\(\)\s*\{[^}]*grantMarketingConsent\(\);/s', $js);
        $this->assertDoesNotMatchRegularExpression('/decline\(\)\s*\{[^}]*grantMarketingConsent\(\);/s', $js);
    }

    /**
     * The CSP has to keep allowing what the tags fetch, or gating them
     * correctly would still leave them broken once consent is given.
     */
    public function test_the_content_security_policy_still_allows_the_vendors(): void
    {
        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy-Report-Only');

        foreach ([
            'https://connect.facebook.net',
            'https://www.googletagmanager.com',
            'https://analytics.tiktok.com',
        ] as $vendor) {
            $this->assertStringContainsString($vendor, $csp);
        }
    }
}
