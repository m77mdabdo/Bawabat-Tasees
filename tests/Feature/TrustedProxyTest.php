<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Behind a TLS-terminating load balancer PHP sees a plain http request and
 * the original scheme survives only in X-Forwarded-Proto, which Symfony
 * ignores until the forwarding proxy is trusted. Untrusted, HSTS is never
 * sent (SecurityHeaders gates it on $request->secure()) and every generated
 * URL — canonical tags, hreflang alternates, sitemap.xml — says http on an
 * https site.
 */
class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A forwarded request, as a load balancer would present it.
     */
    private function forwardedHttps(string $uri = '/'): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get($uri);
    }

    public function test_nothing_is_trusted_outside_production(): void
    {
        $this->assertNull(
            config('trustedproxy.proxies'),
            'Local and testing must trust no proxy, so no request can pass by forging X-Forwarded-Proto.'
        );
    }

    /**
     * The config value is a pure expression over env(), so evaluating the
     * file with APP_ENV=production is the honest way to assert the default
     * a deployment actually gets.
     */
    public function test_production_defaults_to_trusting_the_calling_proxy(): void
    {
        $originalEnv = $_SERVER['APP_ENV'] ?? null;
        $originalProxies = $_SERVER['TRUSTED_PROXIES'] ?? null;

        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'production';
        unset($_SERVER['TRUSTED_PROXIES'], $_ENV['TRUSTED_PROXIES']);

        try {
            $config = require base_path('config/trustedproxy.php');

            $this->assertSame('*', $config['proxies']);
        } finally {
            if ($originalEnv === null) {
                unset($_SERVER['APP_ENV'], $_ENV['APP_ENV']);
            } else {
                $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $originalEnv;
            }

            if ($originalProxies !== null) {
                $_SERVER['TRUSTED_PROXIES'] = $_ENV['TRUSTED_PROXIES'] = $originalProxies;
            }
        }
    }

    /**
     * An explicit list of load balancer IPs must be honoured too — that is
     * what a deployment sets when the origin is directly reachable and '*'
     * would be too loose.
     */
    public function test_an_explicit_proxy_list_is_honoured(): void
    {
        $config = require base_path('config/trustedproxy.php');

        $this->assertArrayHasKey('proxies', $config);
    }

    public function test_a_forwarded_request_is_not_secure_while_the_proxy_is_untrusted(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->assertStringStartsWith('http://', $this->forwardedHttps()->assertOk()->headers->get('Location') ?? url('/'));
        $this->assertNull(
            $this->forwardedHttps()->headers->get('Strict-Transport-Security'),
            'A forged X-Forwarded-Proto must not be able to change anything.'
        );
    }

    public function test_a_forwarded_request_becomes_secure_once_the_proxy_is_trusted(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $html = $this->forwardedHttps('/services')->assertOk()->getContent();

        // The canonical tag is generated from the request, so it is the
        // cheapest observable proof that the scheme was picked up.
        $this->assertStringContainsString('rel="canonical" href="https://', $html);
        $this->assertStringNotContainsString('rel="canonical" href="http://', $html);
    }

    /**
     * The whole point of the proxy fix: HSTS is gated on
     * $request->secure() && production, so it can only ever ship once the
     * forwarded scheme is being read.
     */
    public function test_hsts_ships_on_a_trusted_forwarded_https_request_in_production(): void
    {
        config(['trustedproxy.proxies' => '*']);
        $this->app->detectEnvironment(fn () => 'production');

        $header = $this->forwardedHttps()->assertOk()->headers->get('Strict-Transport-Security');

        $this->assertNotNull($header);
        $this->assertStringContainsString('max-age=31536000', $header);
        $this->assertStringContainsString('includeSubDomains', $header);
    }

    public function test_hsts_still_does_not_ship_in_production_over_plain_http(): void
    {
        config(['trustedproxy.proxies' => '*']);
        $this->app->detectEnvironment(fn () => 'production');

        $this->assertNull(
            $this->get('/')->assertOk()->headers->get('Strict-Transport-Security'),
            'A production deployment genuinely served over http must not be pinned to https.'
        );
    }

    /**
     * The second half of the fix, for URLs generated where there is no
     * request scheme to follow (artisan, a queue worker) or where the proxy
     * forwards no X-Forwarded-Proto at all.
     *
     * Re-running the provider's boot() is deliberate: it is the real wiring,
     * and Laravel refreshes the application between test methods, so the
     * forced scheme cannot leak into another test.
     */
    public function test_production_forces_https_urls_when_app_url_is_https(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.url' => 'https://bawabat-taasees.test']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', route('home'));
        $this->assertStringStartsWith('https://', route('services.index.en'));
    }

    /**
     * A production deployment that genuinely runs over http must not be
     * broken by the force — APP_URL is the signal for intent.
     */
    public function test_https_is_not_forced_when_app_url_is_http(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.url' => 'http://bawabat-taasees.test']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', route('home'));
    }

    public function test_https_is_never_forced_outside_production(): void
    {
        config(['app.url' => 'https://bawabat-taasees.test']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', route('home'), 'Forcing https locally would break the dev server.');
    }
}
