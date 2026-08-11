<?php

namespace Tests\Feature\Analytics;

use App\Models\Setting;
use App\Services\Cms\SettingsRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * This site had no cookie consent mechanism before visitor session tracking
 * was built — the banner exists because VisitorTrackingGate treats an absent
 * decision as refusal, so without somewhere to grant consent the feature
 * would correctly record nothing, forever.
 */
class CookieConsentBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'arabic' => ['/', 'ar'],
            'english' => ['/en', 'en'],
        ];
    }

    #[DataProvider('locales')]
    public function test_the_banner_renders_on_the_public_site(string $uri, string $locale): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('x-data="cookieConsent"', $html);
        $this->assertStringContainsString(__('site.consent.message', [], $locale), $html);
        $this->assertStringContainsString(__('site.consent.accept', [], $locale), $html);
        $this->assertStringContainsString(__('site.consent.decline', [], $locale), $html);
    }

    /**
     * Informed consent needs somewhere to read what is being consented to.
     */
    #[DataProvider('locales')]
    public function test_the_banner_links_to_the_privacy_policy_in_the_same_locale(string $uri, string $locale): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $expected = $locale === 'en' ? url('/en/privacy-policy') : url('/privacy-policy');

        $this->assertStringContainsString('href="'.$expected.'"', $html);
    }

    /**
     * Declining must be no harder than accepting. A banner where the refuse
     * button is hidden, greyed or absent is not consent.
     */
    public function test_declining_is_as_prominent_as_accepting(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('@click="decline()"', $html);
        $this->assertStringContainsString('@click="accept()"', $html);
        $this->assertLessThan(
            strpos($html, '@click="accept()"'),
            strpos($html, '@click="decline()"'),
            'Decline comes first in the DOM, so it is first in the tab order too.'
        );
    }

    public function test_the_banner_disappears_when_tracking_is_switched_off(): void
    {
        Setting::updateOrCreate(['key' => 'visitor_sessions_enabled'], ['value' => false, 'group' => 'visitor_sessions']);
        app(SettingsRepository::class)->flush();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('x-data="cookieConsent"', $html);
    }

    public function test_the_beacon_endpoint_and_token_are_exposed_to_the_client(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('name="visitor-beacon-url" content="'.route('visitor.beacon').'"', $html);
        // sendBeacon cannot set headers, so the CSRF token has to be
        // readable from the document and travel as a form field.
        $this->assertStringContainsString('name="csrf-token"', $html);
    }

    public function test_the_banner_is_not_on_the_dashboard(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('x-data="cookieConsent"', $html);
    }

    /**
     * The client mirrors the server's DNT check so a visitor who has already
     * opted out at browser level is never asked to opt in — and the server
     * refuses those requests regardless of what any cookie says.
     */
    public function test_the_client_honours_do_not_track_before_showing_anything(): void
    {
        $js = file_get_contents(resource_path('js/visitor-session.js'));

        $this->assertStringContainsString('navigator.doNotTrack', $js);
        $this->assertStringContainsString('globalPrivacyControl', $js);
        $this->assertStringContainsString('doNotTrackEnabled() || consentState() !== null', $js);
    }

    /**
     * The beacon must never fire for someone who has not agreed. Asserted
     * against the source because the guard is the whole reason the client
     * side is allowed to exist.
     */
    public function test_the_beacon_will_not_start_without_consent(): void
    {
        $js = file_get_contents(resource_path('js/visitor-session.js'));

        $this->assertStringContainsString('if (beaconStarted || !hasConsented() || doNotTrackEnabled())', $js);
        $this->assertStringContainsString('navigator.sendBeacon', $js);
        $this->assertStringContainsString("addEventListener('pagehide'", $js);
        $this->assertStringContainsString("addEventListener('visibilitychange'", $js);
    }
}
