<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * Sweeps every GET route the app registers and asserts none of them
 * error. Catches the recurring trap directly: a sidebar or nav link
 * pointing at a route name that a refactor removed 500s on route(), and
 * a section-by-section test suite will not necessarily notice.
 */
class RouteSweepTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<int, RoutingRoute>
     */
    private function gettableRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true))
            // Routes with required parameters need a real record; those
            // are covered by their own section tests.
            ->filter(fn (RoutingRoute $route) => ! str_contains($route->uri(), '{'))
            ->reject(fn (RoutingRoute $route) => str_starts_with($route->uri(), '_'))
            ->reject(fn (RoutingRoute $route) => in_array($route->uri(), ['sanctum/csrf-cookie', 'up'], true))
            ->values()
            ->all();
    }

    /**
     * Guards the guard: if the filters above ever narrowed to nothing,
     * every sweep below would pass vacuously.
     */
    public function test_the_sweep_actually_covers_both_locales_and_the_dashboard(): void
    {
        $uris = collect($this->gettableRoutes())->map->uri();

        $this->assertGreaterThan(20, $uris->filter(fn ($uri) => ! str_starts_with($uri, 'dashboard'))->count());
        $this->assertGreaterThan(10, $uris->filter(fn ($uri) => str_starts_with($uri, 'en'))->count(), 'English routes missing.');
        $this->assertGreaterThan(10, $uris->filter(fn ($uri) => str_starts_with($uri, 'dashboard'))->count());

        // The two sections built here specifically.
        $this->assertTrue($uris->contains('dashboard/lead-sources'));
        $this->assertTrue($uris->contains('dashboard/contact-messages'));
    }

    public function test_every_public_get_route_responds_in_both_locales(): void
    {
        $failures = [];

        foreach ($this->gettableRoutes() as $route) {
            $name = $route->getName() ?? $route->uri();

            if (str_starts_with((string) $name, 'dashboard.') || str_starts_with($route->uri(), 'dashboard')) {
                continue;
            }

            $status = $this->get('/'.ltrim($route->uri(), '/'))->getStatusCode();

            // Auth screens redirect an unauthenticated visitor or not at
            // all; both are fine, a 500 is not.
            if ($status >= 500) {
                $failures[] = "{$name} ({$route->uri()}) → {$status}";
            }
        }

        $this->assertSame([], $failures, 'Public routes erroring: '.implode(', ', $failures));
    }

    public function test_every_dashboard_get_route_responds_for_an_admin_in_both_locales(): void
    {
        $failures = [];

        foreach (['ar', 'en'] as $locale) {
            $admin = $this->makeAdmin();
            $admin->update(['locale' => $locale]);

            foreach ($this->gettableRoutes() as $route) {
                if (! str_starts_with($route->uri(), 'dashboard')) {
                    continue;
                }

                $status = $this->actingAs($admin)->get('/'.$route->uri())->getStatusCode();

                if ($status !== 200) {
                    $failures[] = "[{$locale}] {$route->getName()} ({$route->uri()}) → {$status}";
                }
            }
        }

        $this->assertSame([], $failures, 'Dashboard routes not returning 200: '.implode(', ', $failures));
    }

    /**
     * Every route name referenced by a sidebar/nav link must resolve.
     */
    public function test_no_view_links_to_a_route_name_that_does_not_exist(): void
    {
        $views = collect(glob(resource_path('views/**/*.blade.php')))
            ->merge(glob(resource_path('views/**/**/*.blade.php')))
            ->merge(glob(resource_path('views/*.blade.php')));

        $missing = [];

        foreach ($views as $file) {
            // (?<!->) excludes $request->route('token'), which reads a
            // route PARAMETER and has nothing to do with route names.
            preg_match_all("/(?<!->)\broute\(\s*'([a-z0-9_.-]+)'/i", (string) file_get_contents($file), $matches);

            foreach ($matches[1] as $routeName) {
                if (Route::getRoutes()->getByName($routeName) === null) {
                    $missing[] = basename($file).' → '.$routeName;
                }
            }
        }

        $this->assertSame([], $missing, 'Views reference unknown route names: '.implode(', ', $missing));
    }

    public function test_a_plain_user_never_reaches_a_dashboard_page(): void
    {
        $user = User::factory()->create();

        foreach ($this->gettableRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'dashboard')) {
                continue;
            }

            $status = $this->actingAs($user)->get('/'.$route->uri())->getStatusCode();

            $this->assertContains($status, [403, 302], "{$route->uri()} leaked to a non-admin ({$status}).");
        }
    }
}
