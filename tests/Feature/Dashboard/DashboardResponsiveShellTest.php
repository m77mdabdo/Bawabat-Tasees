<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * Guards the responsive dashboard shell. These assert on the rendered
 * markup rather than on pixels — a headless test cannot measure a
 * layout — so each one pins a specific decision that a later edit could
 * silently undo.
 */
class DashboardResponsiveShellTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function shell(): string
    {
        return $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk()->getContent();
    }

    // ---------------------------------------------------------------
    // Drawer
    // ---------------------------------------------------------------

    public function test_the_sidebar_is_a_drawer_below_lg_and_static_above_it(): void
    {
        $html = $this->shell();

        $this->assertStringContainsString('lg:static', $html);
        $this->assertStringContainsString('lg:translate-x-0', $html);
        // The hamburger only exists while the drawer does.
        $this->assertStringContainsString('lg:hidden', $html);
    }

    /**
     * Without a state already in the markup, the drawer renders open
     * across the content on a phone until Alpine boots.
     */
    public function test_the_drawer_is_closed_in_the_markup_before_alpine_boots(): void
    {
        $this->assertStringContainsString('data-open="false"', $this->shell());
    }

    /**
     * A drawer that is only translated off-screen is still focusable.
     * The CSS hides it below lg; this pins the hook that CSS keys on.
     */
    public function test_the_closed_drawer_is_removed_from_the_tab_order(): void
    {
        $html = $this->shell();

        $this->assertStringContainsString('dash-drawer', $html);
        $this->assertStringContainsString(":data-open=\"sidebarOpen ? 'true' : 'false'\"", $html);
    }

    public function test_the_drawer_can_be_dismissed_without_navigating(): void
    {
        $html = $this->shell();

        // A visible close button, the backdrop, and Escape.
        $this->assertStringContainsString('aria-label="'.__('dashboard.topbar.close_menu').'"', $html);
        $this->assertStringContainsString('@click="sidebarOpen = false"', $html);
        $this->assertStringContainsString('@keydown.escape.window="sidebarOpen = false"', $html);
    }

    public function test_the_drawer_never_exceeds_the_viewport_width(): void
    {
        $html = $this->shell();

        // w-72 is 288px, which is 77% of a 375px screen; the cap keeps a
        // visible strip of content on anything narrower.
        $this->assertStringContainsString('max-w-[85vw]', $html);
        $this->assertStringContainsString('lg:max-w-none', $html);
    }

    // ---------------------------------------------------------------
    // Top bar
    // ---------------------------------------------------------------

    /**
     * The trigger sits at the row's inline end, so anchoring the panel's
     * START edge to it throws a 12rem panel off-screen at 375px — off
     * the right in ltr and off the left in rtl.
     */
    public function test_the_user_menu_opens_inward_from_the_trigger(): void
    {
        $html = $this->shell();

        $this->assertStringContainsString('end-0', $html);
        $this->assertStringNotContainsString('ltr:origin-top-left rtl:origin-top-right start-0', $html);
    }

    public function test_the_top_bar_controls_meet_the_touch_target_minimum(): void
    {
        $html = $this->shell();

        // Hamburger, locale toggle and avatar trigger.
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'min-h-[44px]'));
    }

    public function test_a_long_user_name_cannot_widen_the_top_bar(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['name' => str_repeat('طويل جدا ', 20)]);

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('max-w-[12rem]', $html);
        $this->assertStringContainsString('truncate', $html);
        $this->assertStringContainsString('shrink-0', $html);
    }

    // ---------------------------------------------------------------
    // No sideways scroll
    // ---------------------------------------------------------------

    public function test_the_content_pane_cannot_scroll_sideways(): void
    {
        $html = $this->shell();

        $this->assertStringContainsString('overflow-x-hidden', $html);
        // min-w-0 lets the flex child actually shrink; without it a wide
        // table sets the pane's width instead of scrolling inside it.
        $this->assertStringContainsString('min-w-0 flex-1', $html);
    }

    public function test_the_shell_height_is_set_in_css_not_by_a_vh_utility(): void
    {
        $html = $this->shell();

        $this->assertStringContainsString('dashboard-shell', $html);
        // Tailwind emits .h-dvh BEFORE .h-screen, so pairing the two
        // classes would silently leave 100vh winning.
        $this->assertStringNotContainsString('h-screen h-dvh', $html);
    }

    // ---------------------------------------------------------------
    // Both directions
    // ---------------------------------------------------------------

    public function test_the_shell_renders_in_both_dashboard_locales(): void
    {
        foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $dir) {
            $admin = $this->makeAdmin();
            $admin->update(['locale' => $locale]);

            $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

            $this->assertStringContainsString('dir="'.$dir.'"', $html);
            // Logical properties, so one class set is correct both ways.
            $this->assertStringContainsString('inset-y-0 end-0', $html);
        }
    }

    /**
     * The backdrop used a class that does not exist (bg-bg-soft0), so it
     * rendered fully transparent and the modal appeared to float over
     * live content.
     */
    public function test_the_modal_backdrop_is_actually_opaque(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get('/profile')->assertOk()->getContent();

        $this->assertStringNotContainsString('bg-bg-soft0', $html);
        $this->assertStringContainsString('absolute inset-0 bg-text-main opacity-75', $html);
    }
}
