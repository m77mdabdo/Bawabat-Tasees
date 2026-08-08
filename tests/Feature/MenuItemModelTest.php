<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase 1 coverage: the schema, the model's href/active resolution, and
 * the seeded system nav.
 */
class MenuItemModelTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Whitelist
    // ---------------------------------------------------------------

    /**
     * The whole point of the whitelist: a menu item can never point at a
     * route that needs a bound parameter, because resolving it would
     * throw and take down the navbar on every page.
     */
    public function test_whitelist_contains_only_parameter_free_registered_routes(): void
    {
        foreach (array_keys(MenuItem::ROUTE_WHITELIST) as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Whitelisted route [{$name}] is not registered.");
            $this->assertStringNotContainsString('{', $route->uri(), "Whitelisted route [{$name}] takes a parameter.");
        }
    }

    public function test_parameter_bound_routes_are_not_whitelisted(): void
    {
        $this->assertArrayNotHasKey('services.show', MenuItem::ROUTE_WHITELIST);
        $this->assertArrayNotHasKey('articles.show', MenuItem::ROUTE_WHITELIST);
    }

    // ---------------------------------------------------------------
    // href resolution
    // ---------------------------------------------------------------

    public function test_route_item_resolves_a_locale_aware_href(): void
    {
        $item = MenuItem::factory()->route('services.index')->create();

        app()->setLocale('ar');
        $this->assertSame(route('services.index'), $item->href());

        app()->setLocale('en');
        $this->assertSame(route('services.index.en'), $item->href());
    }

    public function test_url_item_uses_its_url_verbatim(): void
    {
        $item = MenuItem::factory()->url('https://example.com/x')->create();

        $this->assertSame('https://example.com/x', $item->href());
    }

    public function test_dropdown_parent_has_no_href(): void
    {
        $this->assertNull(MenuItem::factory()->dropdownParent()->create()->href());
    }

    /**
     * A row whose route_name somehow falls outside the whitelist must
     * degrade to "no link" rather than throwing — a single bad row must
     * not 500 every page on the site.
     */
    public function test_an_off_whitelist_route_degrades_to_null_instead_of_throwing(): void
    {
        $item = MenuItem::factory()->create();
        // Bypasses validation deliberately, simulating a bad/legacy row.
        $item->forceFill(['route_name' => 'services.show'])->save();

        $this->assertNull($item->fresh()->href());
    }

    public function test_blank_target_gets_a_noopener_rel(): void
    {
        $blank = MenuItem::factory()->url('https://example.com', '_blank')->create();
        $self = MenuItem::factory()->url('https://example.com')->create();

        $this->assertSame('noopener', $blank->linkRel());
        $this->assertNull($self->linkRel());
    }

    // ---------------------------------------------------------------
    // Active state
    // ---------------------------------------------------------------

    public function test_index_item_is_active_on_its_own_page_and_on_detail_pages(): void
    {
        $item = MenuItem::factory()->route('services.index')->create();
        $service = Service::factory()->create(['slug' => 'company-formation']);

        $this->get(route('services.index'))->assertOk();
        $this->assertTrue($item->isActive(), 'Should be active on services.index');

        // The generalisation that preserves the old navbar behaviour: an
        // .index item stays lit while viewing one of its .show pages.
        $this->get(route('services.show', $service))->assertOk();
        $this->assertTrue($item->isActive(), 'Should stay active on services.show');

        // …but not on an unrelated page.
        $this->get(route('contact'))->assertOk();
        $this->assertFalse($item->isActive(), 'Should not be active on contact');
    }

    /**
     * pages.about and pages.why-invest share a "pages" prefix — a loose
     * prefix match would light both up at once.
     */
    public function test_sibling_page_items_do_not_both_light_up(): void
    {
        $about = MenuItem::factory()->route('pages.about')->create();
        $why = MenuItem::factory()->route('pages.why-invest')->create();

        $this->get(route('pages.about'));

        $this->assertTrue($about->isActive());
        $this->assertFalse($why->isActive());
    }

    // ---------------------------------------------------------------
    // Tree + scopes
    // ---------------------------------------------------------------

    public function test_children_are_ordered_and_hidden_children_are_excluded(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        MenuItem::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);
        $first = MenuItem::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);
        MenuItem::factory()->hidden()->create(['parent_id' => $parent->id, 'sort_order' => 0]);

        $this->assertCount(3, $parent->children);
        $this->assertCount(2, $parent->visibleChildren);
        $this->assertSame($first->id, $parent->visibleChildren->first()->id);
        $this->assertTrue($parent->hasVisibleChildren());
    }

    public function test_a_parent_with_only_hidden_children_is_not_a_dropdown(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        MenuItem::factory()->hidden()->create(['parent_id' => $parent->id]);

        $this->assertFalse($parent->hasVisibleChildren());
    }

    /**
     * The no-orphan rule, enforced at the DB level by nullOnDelete:
     * deleting a parent reparents its children to top level.
     */
    public function test_deleting_a_parent_reparents_children_to_top_level(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        $child = MenuItem::factory()->create(['parent_id' => $parent->id]);

        $parent->delete();

        $this->assertDatabaseHas('menu_items', ['id' => $child->id]);
        $this->assertNull($child->fresh()->parent_id);
    }

    // ---------------------------------------------------------------
    // Seeded system nav
    // ---------------------------------------------------------------

    public function test_seeded_nav_reproduces_the_previous_hardcoded_navbar(): void
    {
        $this->seed();

        $items = MenuItem::topLevel()->ordered()->get();

        $this->assertSame(
            ['home', 'services.index', 'countries.index', 'articles.index', 'pages.about', 'contact'],
            $items->pluck('route_name')->all()
        );
        $this->assertTrue($items->every(fn (MenuItem $i) => $i->is_system));
        $this->assertTrue($items->every(fn (MenuItem $i) => $i->is_visible));
    }

    /**
     * Bakes in the navbar task's outcome: services is a plain link, not a
     * dropdown.
     */
    public function test_seeded_services_item_is_a_childless_direct_link(): void
    {
        $this->seed();

        $services = MenuItem::where('route_name', 'services.index')->firstOrFail();

        $this->assertSame('route', $services->link_type);
        $this->assertFalse($services->hasVisibleChildren());
        $this->assertSame(route('services.index'), $services->href());
    }

    public function test_seeded_labels_are_bilingual(): void
    {
        $this->seed();

        $services = MenuItem::where('route_name', 'services.index')->firstOrFail();

        $this->assertSame(__('site.nav.services', [], 'ar'), $services->getTranslation('label', 'ar'));
        $this->assertSame(__('site.nav.services', [], 'en'), $services->getTranslation('label', 'en'));
    }

    public function test_reseeding_does_not_duplicate_or_clobber_admin_items(): void
    {
        $this->seed();
        $custom = MenuItem::factory()->create(['label' => ['ar' => 'عنصر مخصص']]);

        $this->seed();

        $this->assertSame(6, MenuItem::where('is_system', true)->count());
        $this->assertDatabaseHas('menu_items', ['id' => $custom->id]);
    }

    public function test_reseeding_preserves_a_hidden_system_item(): void
    {
        $this->seed();
        MenuItem::where('route_name', 'contact')->update(['is_visible' => false]);

        $this->seed();

        $this->assertFalse(MenuItem::where('route_name', 'contact')->firstOrFail()->is_visible);
    }
}
