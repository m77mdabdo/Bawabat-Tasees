<?php

namespace Tests\Feature\Dashboard;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Page;
use App\Models\Service;
use App\Models\Testimonial;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * Every CRUD index table collapses to one card per row below `md`. The
 * mechanism is CSS keyed on data-label, so what a headless test can
 * actually verify is that the hooks are present and complete — a cell
 * that silently loses its data-label would render as an unlabelled
 * value in the card with nothing to say what it is.
 */
class DashboardResponsiveTableTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * A table with no rows renders no cells and no actions, so every
     * assertion below would pass vacuously. Each index gets one real
     * row before it is checked.
     */
    private function seedRowFor(string $route): void
    {
        match ($route) {
            'dashboard.services.index' => Service::factory()->create(),
            'dashboard.countries.index' => Country::factory()->create(),
            'dashboard.faqs.index' => Faq::factory()->create(),
            'dashboard.articles.index' => Article::factory()->create(),
            'dashboard.testimonials.index' => Testimonial::factory()->create(),
            'dashboard.comments.index' => Comment::factory()->for(Article::factory())->create(),
            'dashboard.pages.index' => Page::factory()->create(),
            'dashboard.leads.index' => Lead::factory()->create(['source_platform' => 'google']),
            'dashboard.campaigns.index' => Campaign::factory()->create(),
            'dashboard.lead-sources.index' => LeadSource::factory()->create(),
            default => null,
        };
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tablePages(): array
    {
        return [
            'services' => ['dashboard.services.index'],
            'countries' => ['dashboard.countries.index'],
            'faqs' => ['dashboard.faqs.index'],
            'articles' => ['dashboard.articles.index'],
            'testimonials' => ['dashboard.testimonials.index'],
            'comments' => ['dashboard.comments.index'],
            'pages' => ['dashboard.pages.index'],
            'leads' => ['dashboard.leads.index'],
            'campaigns' => ['dashboard.campaigns.index'],
            'lead sources' => ['dashboard.lead-sources.index'],
        ];
    }

    #[DataProvider('tablePages')]
    public function test_every_index_table_opts_into_the_stacked_layout(string $route): void
    {
        $this->seedRowFor($route);

        $html = $this->actingAs($this->makeAdmin())->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('dash-table-stacked', $html, "{$route} still renders a table that cannot stack.");
        // The desktop path is untouched: the scroll container stays.
        $this->assertStringContainsString('overflow-x-auto', $html);
    }

    /**
     * The real risk: a cell drifting out of sync with its header, so the
     * card prints the wrong label against a value. Checked against
     * rendered HTML, with data present, not against the template.
     */
    #[DataProvider('tablePages')]
    public function test_every_data_cell_carries_the_label_of_its_own_column(string $route): void
    {
        $this->seedRowFor($route);

        $html = $this->actingAs($this->makeAdmin())->get(route($route))->assertOk()->getContent();

        preg_match('/<table[^>]*dash-table-stacked.*?<\/table>/s', $html, $table);
        $this->assertNotEmpty($table, "{$route} rendered no stacked table.");

        [$head, $body] = explode('</thead>', $table[0], 2);

        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $head, $th);
        $headers = array_map(fn ($h) => trim(strip_tags($h)), $th[1]);

        // The first body row only; later rows repeat the same structure.
        preg_match('/<tr\b[^>]*>(.*?)<\/tr>/s', $body, $row);
        preg_match_all('/<td\b([^>]*)>/', $row[1] ?? '', $td);

        $labels = [];
        foreach ($td[1] as $attrs) {
            preg_match('/data-label="([^"]*)"/', $attrs, $l);
            $labels[] = $l[1] ?? null;
        }

        $this->assertNotEmpty($labels, "{$route} rendered no cells.");

        // Every column but the trailing actions cell is labelled, in
        // order, with its own header text.
        $expected = array_slice($headers, 0, count($headers) - 1);
        $this->assertSame(
            $expected,
            array_slice($labels, 0, count($expected)),
            "{$route}: card labels do not match the column headers."
        );

        // The actions column stays unlabelled — the CSS gives it its own
        // full-width row at the foot of the card.
        $this->assertNull(end($labels), "{$route}: the actions cell must not be labelled.");
    }

    /**
     * Row actions are the whole reason a table has to stay usable on a
     * phone; an ~18px text link is not a touch target.
     */
    #[DataProvider('tablePages')]
    public function test_row_actions_are_touch_sized(string $route): void
    {
        $this->seedRowFor($route);

        $html = $this->actingAs($this->makeAdmin())->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('dash-action', $html, "{$route} has row actions that are not touch-sized.");
    }

    public function test_the_empty_state_is_not_styled_as_a_card_footer(): void
    {
        // No leads at all: the colspan row must stay a plain centred
        // message rather than picking up the actions-row treatment.
        Lead::query()->forceDelete();

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.leads.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<td colspan="\d+"[^>]*>/', $html);
        $this->assertStringNotContainsString('data-label="" colspan', $html);
    }

    /**
     * The stacked layout must not have cost the desktop table anything.
     */
    public function test_the_desktop_table_still_renders_a_real_header_row(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.leads.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<thead', $html);
        $this->assertStringContainsString(__('dashboard.leads.phone'), $html);
        $this->assertStringContainsString('min-w-full', $html);
    }

    public function test_tables_stack_identically_in_both_dashboard_locales(): void
    {
        Lead::factory()->create(['source_platform' => 'google']);

        foreach (['ar', 'en'] as $locale) {
            $admin = $this->makeAdmin();
            $admin->update(['locale' => $locale]);

            $html = $this->actingAs($admin)->get(route('dashboard.leads.index'))->assertOk()->getContent();

            $this->assertStringContainsString('dash-table-stacked', $html);
            // Labels come from the same __() call as the headers, so they
            // localise with them rather than being hardcoded.
            $this->assertStringContainsString('data-label="'.__('dashboard.leads.phone', [], $locale).'"', $html);
        }
    }
}
