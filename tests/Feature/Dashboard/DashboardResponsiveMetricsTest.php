<?php

namespace Tests\Feature\Dashboard;

use App\Models\ConversionEvent;
use App\Models\Lead;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * Stat tiles and the CSS charts. The recurring trap in both is the `lg`
 * breakpoint: the sidebar becomes permanently visible at exactly 1024px
 * and takes 288px out of the content pane, so a grid that widens at
 * `lg` is at its most cramped precisely when it adds columns.
 */
class DashboardResponsiveMetricsTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function get_(string $route): string
    {
        return $this->actingAs($this->makeAdmin())->get(route($route))->assertOk()->getContent();
    }

    public function test_home_tiles_reflow_one_two_then_wide(): void
    {
        $html = $this->get_('dashboard');

        // 1 col mobile → 2 at sm. The widest step waits for xl, past the
        // point where the sidebar has taken its 288px.
        $this->assertStringContainsString('grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4', $html);
        $this->assertStringContainsString('grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3', $html);
    }

    public function test_no_tile_grid_widens_at_the_breakpoint_where_the_sidebar_appears(): void
    {
        foreach (['dashboard', 'dashboard.reports.index'] as $route) {
            $html = $this->get_($route);

            $this->assertDoesNotMatchRegularExpression(
                '/grid-cols-\d[^"]*\blg:grid-cols-[45]\b/',
                $html,
                "{$route}: a 4- or 5-column grid at lg is squeezed by the sidebar that appears at the same width."
            );
        }
    }

    /**
     * "1,234,567.00" at text-3xl is wider than a two-up tile at 375px.
     */
    public function test_large_figures_cannot_overflow_their_tile(): void
    {
        Lead::factory()
            ->count(3)
            ->create()
            ->each(fn (Lead $lead) => ConversionEvent::factory()->for($lead)->create(['value' => 999999.99]));

        foreach (['dashboard', 'dashboard.reports.index'] as $route) {
            $html = $this->get_($route);

            $this->assertStringContainsString('break-words', $html, "{$route}: a long figure has nothing to break on.");
            $this->assertStringContainsString('tabular-nums', $html);
        }
    }

    public function test_tile_type_scales_down_on_mobile(): void
    {
        $this->assertStringContainsString('text-2xl font-bold tabular-nums', $this->get_('dashboard'));
        $this->assertStringContainsString('sm:text-3xl', $this->get_('dashboard'));
        $this->assertStringContainsString('text-xl font-semibold tabular-nums', $this->get_('dashboard.reports.index'));
        $this->assertStringContainsString('sm:text-2xl', $this->get_('dashboard.reports.index'));
    }

    // ---------------------------------------------------------------
    // Charts
    // ---------------------------------------------------------------

    /**
     * The 90-day range packs 90 bars into ~340px on a phone, which is
     * under 3px each once the gaps are removed.
     */
    public function test_the_spark_chart_keeps_its_bars_legible_and_scrolls_instead(): void
    {
        Lead::factory()->count(5)->create();

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.reports.index', ['days' => 90]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('min-w-[3px]', $html);
        $this->assertStringContainsString('overflow-x-auto', $html);
    }

    public function test_the_spark_chart_still_exposes_its_series_as_a_table(): void
    {
        Lead::factory()->count(3)->create();

        $html = $this->get_('dashboard.reports.index');

        // The bars are decoration; the numbers stay readable without them.
        $this->assertStringContainsString('sr-only', $html);
        $this->assertStringContainsString('<caption>', $html);
    }

    public function test_bar_chart_labels_truncate_rather_than_push_the_number_off(): void
    {
        Lead::factory()->count(2)->create(['source_platform' => 'a-very-long-unrecognised-source-value']);

        $html = $this->get_('dashboard.reports.index');

        $this->assertStringContainsString('min-w-0 flex-1 truncate', $html);
        // Truncated on screen, still available on hover/long-press.
        $this->assertStringContainsString('title="a-very-long-unrecognised-source-value"', $html);
    }

    // ---------------------------------------------------------------
    // Spacing
    // ---------------------------------------------------------------

    /**
     * x-card carries no padding of its own, so a bare <x-card> rendered
     * its content flush against the border.
     */
    public function test_report_and_campaign_cards_are_not_flush_against_their_border(): void
    {
        $this->assertStringNotContainsString('<x-card>', $this->get_('dashboard.reports.index'));
        $this->assertStringContainsString('p-4 sm:p-6', $this->get_('dashboard.reports.index'));
    }

    public function test_reports_renders_in_both_locales_at_every_range(): void
    {
        Lead::factory()->count(3)->create();

        foreach (['ar', 'en'] as $locale) {
            $admin = $this->makeAdmin();
            $admin->update(['locale' => $locale]);

            foreach ([7, 30, 90] as $days) {
                $this->actingAs($admin)
                    ->get(route('dashboard.reports.index', ['days' => $days]))
                    ->assertOk();
            }
        }
    }
}
