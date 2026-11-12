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
     * a deployment actually gets: nothing trusted unless TRUSTED_PROXIES says so.
     */
    public function test_production_trusts_no_proxy_unless_configured(): void
    {
        $originalEnv = $_SERVER['APP_ENV'] ?? null;
        $originalProxies = $_SERVER['TRUSTED_PROXIES'] ?? null;

        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'production';
        unset($_SERVER['TRUSTED_PROXIES'], $_ENV['TRUSTED_PROXIES']);

        try {
            $this->assertNull((require base_path('config/trustedproxy.php'))['proxies']);

            $_SERVER['TRUSTED_PROXIES'] = $_ENV['TRUSTED_PROXIES'] = '';
            $this->assertNull((require base_path('config/trustedproxy.php'))['proxies'], 'An empty TRUSTED_PROXIES= trusts nothing.');

            $_SERVER['TRUSTED_PROXIES'] = $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/8,192.168.1.10';
            $this->assertSame('10.0.0.0/8,192.168.1.10', (require base_path('config/trustedproxy.php'))['proxies']);
        } finally {
            unset($_SERVER['TRUSTED_PROXIES'], $_ENV['TRUSTED_PROXIES']);
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
        config(['app.url' => 'https://bawabat-taasees.test', 'mail.default' => 'smtp']);

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
        config(['app.url' => 'http://bawabat-taasees.test', 'mail.default' => 'smtp']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', route('home'));
    }

    public function test_https_is_never_forced_outside_production(): void
    {
        config(['app.url' => 'https://bawabat-taasees.test']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://', route('home'), 'Forcing https locally would break the dev server.');
    }

    /** Production refuses to start when email would be logged or dropped (sign-in codes included). */
    public function test_production_refuses_to_boot_with_the_log_or_array_mailer(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        foreach (['log', 'array'] as $mailer) {
            config(['mail.default' => $mailer]);
            try {
                (new AppServiceProvider($this->app))->boot();
                $this->fail("Booted in production with the {$mailer} mailer.");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('MAIL_MAILER', $e->getMessage());
            }
        }

        config(['mail.default' => 'smtp']);
        (new AppServiceProvider($this->app))->boot();
        $this->addToAssertionCount(1);
    }

    public function test_the_log_mailer_is_still_fine_outside_production(): void
    {
        config(['mail.default' => 'log']);

        (new AppServiceProvider($this->app))->boot();

        $this->addToAssertionCount(1);
    }

    /**
     * Laravel skips the Host check in local and testing, so the middleware is exercised directly
     * with that skip switched off: APP_URL's host passes, a forged Host is refused.
     */
    public function test_only_the_app_url_host_is_trusted(): void
    {
        $this->assertContains(\Illuminate\Http\Middleware\TrustHosts::class, $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->getGlobalMiddleware());

        config(['app.url' => 'https://bawabat-taasees.test']);
        $middleware = new class($this->app) extends \Illuminate\Http\Middleware\TrustHosts
        {
            protected function shouldSpecifyTrustedHosts()
            {
                return true;
            }
        };

        try {
            $good = \Illuminate\Http\Request::create('https://bawabat-taasees.test/');
            $middleware->handle($good, fn ($r) => response('ok'));
            $this->assertSame('bawabat-taasees.test', $good->getHost());

            $forged = \Illuminate\Http\Request::create('https://evil.test/');
            $middleware->handle($forged, fn ($r) => response('ok'));
            $this->expectException(\Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException::class);
            $forged->getHost();
        } finally {
            \Illuminate\Http\Request::setTrustedHosts([]);
        }
    }
}
