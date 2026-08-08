<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lead;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * Forms, the menu manager and the detail pages at narrow widths.
 */
class DashboardResponsiveFormTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function get_(string $route, mixed $param = null): string
    {
        return $this->actingAs($this->makeAdmin())
            ->get($param === null ? route($route) : route($route, $param))
            ->assertOk()
            ->getContent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function createForms(): array
    {
        return [
            'services' => ['dashboard.services.create'],
            'countries' => ['dashboard.countries.create'],
            'faqs' => ['dashboard.faqs.create'],
            'articles' => ['dashboard.articles.create'],
            'testimonials' => ['dashboard.testimonials.create'],
            'campaigns' => ['dashboard.campaigns.create'],
            'lead sources' => ['dashboard.lead-sources.create'],
            'menu' => ['dashboard.menu.create'],
        ];
    }

    /**
     * Every paired field must be one column on a phone. md (768px) is
     * the right step here: it is above the 375/414 phone widths and
     * below the point where the sidebar takes its 288px.
     */
    #[DataProvider('createForms')]
    public function test_paired_fields_are_single_column_on_mobile(string $route): void
    {
        $html = $this->get_($route);

        $this->assertMatchesRegularExpression(
            '/grid-cols-1 (md|sm):grid-cols-[23]/',
            $html,
            "{$route}: a form grid does not start at one column."
        );
        $this->assertDoesNotMatchRegularExpression(
            '/class="[^"]*\bgrid grid-cols-[234]\b/',
            $html,
            "{$route}: a form grid is multi-column at the smallest width."
        );
    }

    #[DataProvider('createForms')]
    public function test_the_form_footer_wraps_and_its_cancel_link_is_a_touch_target(string $route): void
    {
        $html = $this->get_($route);

        $this->assertStringContainsString('mt-6 flex flex-wrap items-center justify-end gap-4', $html);
        $this->assertStringContainsString('dash-action text-sm text-text-secondary underline', $html);
    }

    /**
     * A checkbox is 16px. The 44px target has to be the padded label
     * around it — stretching the box knocks it out of alignment with
     * its own text.
     */
    public function test_checkbox_rows_are_touch_sized_through_their_label(): void
    {
        $html = $this->get_('dashboard.services.create');

        $this->assertStringContainsString('inline-flex min-h-[44px] cursor-pointer items-center py-2', $html);
        // Two toggles side by side must be able to wrap at 375px.
        $this->assertStringContainsString('flex flex-wrap items-center gap-x-6 gap-y-1', $html);
    }

    public function test_file_inputs_have_a_usable_native_button(): void
    {
        $html = $this->get_('dashboard.services.create');

        $this->assertStringContainsString('file:min-h-[44px]', $html);
        $this->assertStringContainsString('file:cursor-pointer', $html);
        $this->assertStringContainsString('w-full max-w-full', $html);
    }

    public function test_settings_and_tracking_forms_stay_single_column_on_mobile(): void
    {
        $settings = $this->get_('dashboard.settings.edit');
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2', $settings);

        $tracking = $this->get_('dashboard.tracking-settings.edit');
        $this->assertStringContainsString('flex flex-col gap-4 sm:flex-row', $tracking);
        $this->assertStringContainsString('min-h-[44px] shrink-0 cursor-pointer', $tracking);
    }

    // ---------------------------------------------------------------
    // Page headers
    // ---------------------------------------------------------------

    public function test_page_headers_wrap_instead_of_crushing_their_action_button(): void
    {
        foreach (['dashboard.services.index', 'dashboard.menu.index', 'dashboard.lead-sources.index'] as $route) {
            $html = $this->get_($route);

            $this->assertStringContainsString('flex flex-wrap items-center justify-between gap-3', $html, $route);
            // The button keeps its own line rather than breaking mid-word.
            $this->assertStringContainsString('min-h-[44px] shrink-0 items-center whitespace-nowrap', $html, $route);
        }
    }

    // ---------------------------------------------------------------
    // Menu manager
    // ---------------------------------------------------------------

    /**
     * The reorder row carries four 44px buttons plus an edit link. A
     * flex-1 label has flex-basis 0, so it contributes nothing to the
     * wrap calculation and never forces a wrap — it just shrinks toward
     * zero while the controls stay on the line.
     */
    public function test_the_menu_label_takes_its_own_row_on_mobile(): void
    {
        MenuItem::factory()->create();

        $html = $this->get_('dashboard.menu.index');

        $this->assertStringContainsString('min-w-0 w-full truncate', $html);
        $this->assertStringContainsString('sm:w-auto sm:flex-1', $html);
        // The four reorder controls stay together as one unit.
        $this->assertStringContainsString('flex shrink-0 items-center gap-1', $html);
    }

    public function test_the_menu_reorder_controls_stay_touch_sized(): void
    {
        MenuItem::factory()->create();

        $html = $this->get_('dashboard.menu.index');

        $this->assertStringContainsString('min-h-[44px] min-w-[44px]', $html);
    }

    // ---------------------------------------------------------------
    // Detail pages
    // ---------------------------------------------------------------

    /**
     * dt was shrink-0, so a long Arabic label kept its full width and
     * squeezed the value into a narrow ragged column at 375px.
     */
    public function test_detail_rows_stack_label_over_value_on_mobile(): void
    {
        $lead = Lead::factory()->create([
            'landing_page_url' => 'https://example.com/'.str_repeat('a-long-path/', 8),
        ]);

        $html = $this->get_('dashboard.leads.show', $lead);

        $this->assertStringContainsString('flex flex-col gap-0.5 text-sm sm:flex-row', $html);
        $this->assertStringContainsString('text-text-secondary sm:shrink-0', $html);
        // A long URL breaks instead of widening the page.
        $this->assertStringContainsString('break-all', $html);
    }

    public function test_the_profile_modal_fits_a_narrow_viewport(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('mt-1 block w-full sm:w-3/4', $html);
        $this->assertStringContainsString('mt-6 flex flex-wrap justify-end gap-3', $html);
    }

    public function test_every_dashboard_form_page_renders_in_both_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $admin = $this->makeAdmin();
            $admin->update(['locale' => $locale]);

            foreach (array_map(fn ($r) => $r[0], self::createForms()) as $route) {
                $this->actingAs($admin)->get(route($route))->assertOk();
            }

            $this->actingAs($admin)->get(route('dashboard.settings.edit'))->assertOk();
            $this->actingAs($admin)->get(route('dashboard.tracking-settings.edit'))->assertOk();
            $this->actingAs($admin)->get('/profile')->assertOk();
        }
    }
}
