<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The three helpers in app/helpers.php. Two of them —
 * current_route_base_name() and route_in_locale() — appeared in no test file
 * at all, despite driving the navbar active state, the language toggle and
 * every hreflang tag on the site.
 *
 * They are exercised through real requests rather than called in isolation,
 * because both read Route::currentRouteName() and request()->route(): calling
 * them with no request in flight tests something the application never does.
 */
class HelpersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Runs a callback inside a real request to the given URI, so the route
     * context the helpers depend on is genuinely populated.
     */
    private function during(string $uri, \Closure $assertions): void
    {
        $this->app['router']->get('__helper_probe', fn () => 'ok');

        $captured = null;

        $this->app['router']->matched(function ($event) use (&$captured) {
            $captured = $event;
        });

        // A view composer is the honest hook: it runs mid-request, with the
        // route resolved, which is exactly when these helpers are called.
        View::composer('layouts.public', function () use ($assertions) {
            $assertions();
        });

        $this->get($uri)->assertOk();
    }

    // -----------------------------------------------------------------
    // current_route_base_name()
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function routeNames(): array
    {
        return [
            'arabic home' => ['/', 'home'],
            'english home' => ['/en', 'home'],
            'arabic services' => ['/services', 'services.index'],
            'english services' => ['/en/services', 'services.index'],
            'arabic contact' => ['/contact', 'contact'],
            'english contact' => ['/en/contact', 'contact'],
        ];
    }

    #[DataProvider('routeNames')]
    public function test_the_en_suffix_is_stripped_from_the_current_route_name(string $uri, string $expected): void
    {
        $this->during($uri, function () use ($expected) {
            $this->assertSame($expected, current_route_base_name());
        });
    }

    public function test_a_parameterised_route_keeps_its_full_base_name(): void
    {
        $service = Service::factory()->create();

        $this->during('/en/services/'.$service->slug, function () {
            $this->assertSame('services.show', current_route_base_name());
        });
    }

    /**
     * A name that merely CONTAINS ".en" mid-string must not be truncated —
     * only a trailing suffix is a locale marker.
     */
    public function test_only_a_trailing_suffix_is_treated_as_the_locale_marker(): void
    {
        $this->app['router']->get('__probe_enquiry', fn () => current_route_base_name())->name('pages.enquiry');

        $this->assertSame('pages.enquiry', $this->get('__probe_enquiry')->assertOk()->getContent());
    }

    public function test_it_returns_null_when_no_route_is_named(): void
    {
        $this->app['router']->get('__probe_unnamed', fn () => current_route_base_name() ?? 'NULL');

        $this->assertSame('NULL', $this->get('__probe_unnamed')->assertOk()->getContent());
    }

    // -----------------------------------------------------------------
    // route_in_locale()
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function localeSwaps(): array
    {
        return [
            'home ar to en' => ['/', 'en', '/en'],
            'home en to ar' => ['/en', 'ar', '/'],
            'services ar to en' => ['/services', 'en', '/en/services'],
            'services en to ar' => ['/en/services', 'ar', '/services'],
            'contact ar to en' => ['/contact', 'en', '/en/contact'],
            // Not a CMS-backed page: /privacy-policy 404s without its seeded
            // Page row, which would be testing the seeder, not the helper.
            'consultation en to ar' => ['/en/consultation', 'ar', '/consultation'],
            'faqs ar to en' => ['/faqs', 'en', '/en/faqs'],
        ];
    }

    #[DataProvider('localeSwaps')]
    public function test_it_resolves_the_same_page_in_the_other_locale(string $uri, string $locale, string $expectedPath): void
    {
        $this->during($uri, function () use ($locale, $expectedPath) {
            $this->assertSame(url($expectedPath), route_in_locale($locale));
        });
    }

    /**
     * The behaviour the language toggle actually depends on: the slug has to
     * survive the swap, or switching language on a detail page dumps the
     * visitor somewhere else entirely.
     */
    public function test_route_parameters_survive_the_swap(): void
    {
        $service = Service::factory()->create(['slug' => 'llc-formation']);

        $this->during('/services/llc-formation', function () {
            $this->assertSame(url('/en/services/llc-formation'), route_in_locale('en'));
        });

        $this->during('/en/services/llc-formation', function () {
            $this->assertSame(url('/services/llc-formation'), route_in_locale('ar'));
        });
    }

    public function test_asking_for_the_locale_you_are_already_in_returns_the_same_page(): void
    {
        $this->during('/services', function () {
            $this->assertSame(url('/services'), route_in_locale('ar'));
        });
    }

    /**
     * Dashboard and auth routes have no English variant, so the helper must
     * return null rather than 500 on a route() call for a name that does not
     * exist — this is what keeps hreflang tags off those pages.
     */
    public function test_it_returns_null_for_a_route_with_no_english_variant(): void
    {
        $this->app['router']->get('__probe_single_locale', fn () => route_in_locale('en') ?? 'NULL')
            ->name('probe.single');

        $this->assertSame('NULL', $this->get('__probe_single_locale')->assertOk()->getContent());
    }

    public function test_an_unnamed_route_yields_null(): void
    {
        $this->app['router']->get('__probe_unnamed_locale', fn () => route_in_locale('en') ?? 'NULL');

        $this->assertSame('NULL', $this->get('__probe_unnamed_locale')->assertOk()->getContent());
    }

    // -----------------------------------------------------------------
    // lroute()
    // -----------------------------------------------------------------

    public function test_lroute_follows_the_current_locale(): void
    {
        app()->setLocale('ar');
        $this->assertSame(url('/services'), lroute('services.index'));

        app()->setLocale('en');
        $this->assertSame(url('/en/services'), lroute('services.index'));
    }

    /**
     * Always safe to call: a single-locale name has to fall straight through
     * rather than throw, which is why views can use it everywhere without an
     * if/else.
     */
    public function test_lroute_falls_through_for_a_name_with_no_english_variant(): void
    {
        app()->setLocale('en');

        $this->assertSame(route('login'), lroute('login'));
        $this->assertSame(route('dashboard'), lroute('dashboard'));
    }

    public function test_lroute_does_not_double_suffix_an_already_english_name(): void
    {
        app()->setLocale('en');

        $this->assertSame(url('/en/services'), lroute('services.index.en'));
    }

    public function test_lroute_passes_parameters_through(): void
    {
        $service = Service::factory()->create(['slug' => 'branch-office']);

        app()->setLocale('en');
        $this->assertSame(url('/en/services/branch-office'), lroute('services.show', $service));
    }
}
