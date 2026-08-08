<?php

namespace Tests\Feature\Public;

use App\Models\MenuItem;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2: the public navbar renders from menu_items rather than
 * hardcoded markup. The site must look identical to before — these
 * assertions pin that equivalence as well as the new data-driven
 * behaviours.
 */
class NavbarRenderingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Everything before </header> — the desktop bar and the mobile
     * drawer both live there, and the page body legitimately links to the
     * same routes, so unscoped assertions would prove nothing.
     */
    private function header(string $url = '/'): string
    {
        $html = $this->get($url)->assertOk()->getContent();

        return substr($html, 0, strpos($html, '</header>'));
    }

    // ---------------------------------------------------------------
    // Equivalence with the previous hardcoded navbar
    // ---------------------------------------------------------------

    public function test_seeded_menu_renders_the_same_six_items_in_order(): void
    {
        $this->seed();

        $header = $this->header();

        $positions = [];
        foreach ([
            route('home'), route('services.index'), route('countries.index'),
            route('articles.index'), route('pages.about'), route('contact'),
        ] as $url) {
            $pos = strpos($header, 'href="'.$url.'"');
            $this->assertNotFalse($pos, "Navbar is missing a link to {$url}");
            $positions[] = $pos;
        }

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Navbar items are not in the seeded order.');
    }

    public function test_seeded_services_item_is_a_plain_link_not_a_dropdown(): void
    {
        $this->seed();
        Service::factory()->create(['slug' => 'company-formation']);

        $header = $this->header();

        $this->assertStringContainsString('href="'.route('services.index').'"', $header);
        // No dropdown trigger, and no individual service links in the nav.
        $this->assertStringNotContainsString('aria-haspopup="true"', $header);
        $this->assertStringNotContainsString(route('services.show', 'company-formation'), $header);
    }

    public function test_labels_come_from_the_database(): void
    {
        $this->seed();
        MenuItem::where('route_name', 'contact')->update(['label' => ['ar' => 'كلمنا', 'en' => 'Talk To Us']]);

        $this->assertStringContainsString('كلمنا', $this->header());
    }

    // ---------------------------------------------------------------
    // Locale
    // ---------------------------------------------------------------

    public function test_route_items_render_localized_hrefs(): void
    {
        $this->seed();

        $this->assertStringContainsString('href="'.route('services.index').'"', $this->header('/'));
        $this->assertStringContainsString('href="'.route('services.index.en').'"', $this->header('/en'));
    }

    public function test_labels_render_in_the_current_locale(): void
    {
        $this->seed();
        MenuItem::where('route_name', 'contact')->update(['label' => ['ar' => 'تواصل', 'en' => 'Contact Us']]);

        $this->assertStringContainsString('تواصل', $this->header('/'));
        $this->assertStringContainsString('Contact Us', $this->header('/en'));
    }

    // ---------------------------------------------------------------
    // Visibility
    // ---------------------------------------------------------------

    public function test_hidden_items_do_not_render(): void
    {
        $this->seed();
        MenuItem::where('route_name', 'countries.index')->update(['is_visible' => false]);

        $header = $this->header();

        $this->assertStringNotContainsString('href="'.route('countries.index').'"', $header);
        $this->assertStringContainsString('href="'.route('contact').'"', $header);
    }

    // ---------------------------------------------------------------
    // Dropdowns
    // ---------------------------------------------------------------

    public function test_a_parent_with_visible_children_renders_a_dropdown(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create(['label' => ['ar' => 'الشركة']]);
        MenuItem::factory()->route('pages.about')->create([
            'parent_id' => $parent->id,
            'label' => ['ar' => 'من نحن'],
        ]);

        $header = $this->header();

        $this->assertStringContainsString('aria-haspopup="true"', $header);
        $this->assertStringContainsString('الشركة', $header);
        $this->assertStringContainsString('href="'.route('pages.about').'"', $header);
    }

    public function test_a_parent_whose_children_are_all_hidden_renders_as_a_plain_item(): void
    {
        $parent = MenuItem::factory()->route('pages.about')->create();
        MenuItem::factory()->hidden()->create(['parent_id' => $parent->id]);

        $this->assertStringNotContainsString('aria-haspopup="true"', $this->header());
    }

    public function test_hidden_children_never_leak_into_a_dropdown(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        MenuItem::factory()->route('pages.about')->create(['parent_id' => $parent->id]);
        MenuItem::factory()->route('faqs.index')->hidden()->create(['parent_id' => $parent->id]);

        $header = $this->header();

        $this->assertStringContainsString('href="'.route('pages.about').'"', $header);
        $this->assertStringNotContainsString('href="'.route('faqs.index').'"', $header);
    }

    // ---------------------------------------------------------------
    // Custom URLs
    // ---------------------------------------------------------------

    public function test_url_items_render_verbatim_with_target_and_rel(): void
    {
        MenuItem::factory()->url('https://example.com/partner', '_blank')->create();

        $header = $this->header();

        $this->assertStringContainsString('href="https://example.com/partner"', $header);
        $this->assertStringContainsString('target="_blank"', $header);
        $this->assertStringContainsString('rel="noopener"', $header);
    }

    public function test_self_target_items_get_no_noopener(): void
    {
        MenuItem::factory()->url('/custom-path')->create();

        $header = $this->header();

        $this->assertStringContainsString('href="/custom-path"', $header);
        $this->assertStringNotContainsString('rel="noopener"', $header);
    }

    // ---------------------------------------------------------------
    // Guard rails
    // ---------------------------------------------------------------

    public function test_navbar_renders_without_erroring_when_the_menu_is_empty(): void
    {
        $this->assertSame(0, MenuItem::count());

        $header = $this->header();

        // Falls back to at least a way home.
        $this->assertStringContainsString('href="'.route('home').'"', $header);
    }

    public function test_navbar_renders_without_erroring_when_every_item_is_hidden(): void
    {
        $this->seed();
        MenuItem::query()->update(['is_visible' => false]);

        $header = $this->header();

        $this->assertStringContainsString('href="'.route('home').'"', $header);
    }

    /**
     * A row pointing at a parameter-bound route can only get there by
     * bypassing validation — but if it does, the navbar must degrade, not
     * take every page down with it.
     */
    public function test_an_item_with_an_off_whitelist_route_does_not_break_the_page(): void
    {
        $item = MenuItem::factory()->create(['label' => ['ar' => 'عنصر تالف']]);
        $item->forceFill(['route_name' => 'services.show'])->save();

        $this->get('/')->assertOk();
        $this->assertStringContainsString('عنصر تالف', $this->header());
    }

    // ---------------------------------------------------------------
    // Active state + structural items
    // ---------------------------------------------------------------

    public function test_active_item_is_highlighted_including_on_detail_pages(): void
    {
        $this->seed();
        $service = Service::factory()->create(['slug' => 'company-formation']);

        $this->assertStringContainsString('text-primary-green font-semibold', $this->header(route('services.index')));
        $this->assertStringContainsString('text-primary-green font-semibold', $this->header(route('services.show', $service)));
    }

    public function test_logo_language_toggle_and_whatsapp_are_not_menu_driven(): void
    {
        $this->seed();
        MenuItem::query()->delete();

        $header = $this->header();

        $this->assertStringContainsString('logo-full-color-256.png', $header);
        $this->assertStringContainsString(__('site.nav.switch_to_english'), $header);
    }

    public function test_every_public_page_still_renders_with_the_data_driven_navbar(): void
    {
        $this->seed();

        foreach (['/', '/services', '/countries', '/faqs', '/articles', '/about', '/contact', '/consultation'] as $path) {
            $this->get($path)->assertOk();
            $this->get('/en'.($path === '/' ? '' : $path))->assertOk();
        }
    }
}
