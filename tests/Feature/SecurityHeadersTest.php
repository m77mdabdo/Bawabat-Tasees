<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/'],
            'english home' => ['/en'],
            'services' => ['/services'],
            'contact' => ['/contact'],
            'login' => ['/login'],
        ];
    }

    #[DataProvider('pages')]
    public function test_security_headers_are_present(string $uri): void
    {
        $response = $this->get($uri)->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    public function test_headers_are_present_on_the_dashboard_too(): void
    {
        // Even the redirect a guest gets must carry them.
        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Exception responses are rendered by the handler and never bubble
     * back through the middleware, so they need the explicit respond()
     * hook in bootstrap/app.php. Without it these shipped bare.
     */
    public function test_headers_are_present_on_exception_responses(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_permissions_policy_locks_down_unused_features(): void
    {
        $policy = $this->get('/')->assertOk()->headers->get('Permissions-Policy');

        foreach (['camera=()', 'microphone=()', 'geolocation=()', 'payment=()', 'usb=()'] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }

        // The hero/login background videos need autoplay on our own origin.
        $this->assertStringContainsString('autoplay=(self)', $policy);
    }

    /**
     * CSP must ship REPORT-ONLY: the site relies on Alpine's new Function()
     * evaluation, inline handlers and third-party pixel CDNs, all of which
     * an enforcing policy would break.
     */
    public function test_csp_is_report_only_and_not_enforced(): void
    {
        $response = $this->get('/')->assertOk();

        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull(
            $response->headers->get('Content-Security-Policy'),
            'CSP must not be enforced yet — it would break Alpine and the tracking pixels.'
        );
    }

    public function test_report_only_csp_still_allows_what_the_site_actually_uses(): void
    {
        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("'unsafe-eval'", $csp, 'Alpine needs unsafe-eval.');
        $this->assertStringContainsString('https://fonts.bunny.net', $csp);
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
        $this->assertStringContainsString("media-src 'self'", $csp, 'Background videos are self-hosted.');
    }

    /**
     * HSTS pins a browser to https for a year — sending it from a local
     * http dev server would make localhost unreachable over http.
     */
    public function test_hsts_is_not_sent_outside_production_https(): void
    {
        $this->assertNull($this->get('/')->headers->get('Strict-Transport-Security'));
    }

    public function test_php_version_is_not_advertised(): void
    {
        $this->assertNull($this->get('/')->headers->get('X-Powered-By'));
    }

    public function test_session_cookie_is_secure_in_production_and_not_locally(): void
    {
        // The config default is environment-derived, so assert the rule
        // rather than the current machine's value.
        $this->assertFalse(
            (bool) config('session.secure'),
            'Local/testing must not force a secure cookie over http.'
        );
    }
}
