<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * The accessibility gaps the launch-readiness audit found, held closed.
 *
 * Nothing here can prove the *behaviour* — a focus trap and a skip link are
 * only really provable in a browser. What these assert is that the mechanism
 * is present and wired to the right thing, which is what actually regressed
 * historically: a drawer shipped without a trap, a control shipped without a
 * label.
 */
class AccessibilityTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    // -----------------------------------------------------------------
    // Skip link (WCAG 2.4.1)
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'english home' => ['/en'],
            'services' => ['/services'],
            'articles' => ['/articles'],
            'contact' => ['/contact'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_every_public_page_starts_with_a_skip_link(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('href="#main-content"', $html);
        $this->assertStringContainsString('id="main-content"', $html);

        // Hidden until focused, so it costs the design nothing.
        $this->assertStringContainsString('sr-only focus:not-sr-only', $html);

        // The link has to come before the header, or it is not a skip link.
        $this->assertLessThan(
            strpos($html, '<header'),
            strpos($html, 'href="#main-content"'),
            'The skip link must precede the header.'
        );
    }

    #[DataProvider('publicPages')]
    public function test_the_skip_target_can_actually_receive_focus(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        // Without tabindex="-1" the browser scrolls to <main> but leaves
        // focus on the link, so the next Tab goes back into the navbar.
        $this->assertMatchesRegularExpression(
            '/<main id="main-content" tabindex="-1"/',
            $html
        );
    }

    public function test_the_dashboard_has_a_skip_link_too(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('href="#main-content"', $html);
        $this->assertStringContainsString('id="main-content" tabindex="-1"', $html);
    }

    public function test_the_skip_link_is_translated(): void
    {
        $this->assertStringContainsString(__('site.nav.skip_to_content', [], 'ar'), $this->get('/')->getContent());
        $this->assertStringContainsString(__('site.nav.skip_to_content', [], 'en'), $this->get('/en')->getContent());
    }

    // -----------------------------------------------------------------
    // Drawers
    // -----------------------------------------------------------------

    public function test_the_public_drawer_is_a_trapped_dialog(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('x-trap.noscroll="mobileOpen"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('@keydown.escape.window="mobileOpen = false"', $html);
    }

    public function test_the_public_drawer_dialog_is_labelled_in_both_locales(): void
    {
        foreach (['/' => 'ar', '/en' => 'en'] as $uri => $locale) {
            $this->assertStringContainsString(
                'aria-label="'.__('site.nav.menu_dialog_label', [], $locale).'"',
                $this->get($uri)->assertOk()->getContent()
            );
        }
    }

    public function test_the_dashboard_drawer_traps_focus_and_closes_on_escape(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('x-trap.noscroll="sidebarOpen"', $html);
        $this->assertStringContainsString('@keydown.escape.window="sidebarOpen = false"', $html);
    }

    // -----------------------------------------------------------------
    // Dropdown
    // -----------------------------------------------------------------

    public function test_the_dashboard_user_menu_announces_and_restores_state(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString(':aria-expanded="open ? \'true\' : \'false\'"', $html);
        $this->assertStringContainsString('aria-haspopup="true"', $html);
        // Escape closes it AND hands focus back, rather than stranding the
        // keyboard user at the top of the document.
        $this->assertStringContainsString('@keydown.escape.stop', $html);
        $this->assertStringContainsString('$refs.trigger', $html);
    }

    // -----------------------------------------------------------------
    // Pause controls (WCAG 2.2.2)
    // -----------------------------------------------------------------

    public function test_the_hero_video_can_be_paused(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('x-data="backgroundVideo"', $html);
        $this->assertStringContainsString('x-ref="video"', $html);
        $this->assertStringContainsString(__('site.motion.pause_video', [], 'ar'), $html);
        $this->assertStringContainsString('aria-pressed', $html);
    }

    public function test_the_login_background_can_be_paused(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('x-data="backgroundVideo"', $html);
        $this->assertStringContainsString('x-ref="video"', $html);
        $this->assertStringContainsString(__('site.motion.pause_video', [], 'ar'), $html);
    }

    public function test_the_pause_control_is_translated(): void
    {
        $this->assertStringContainsString(__('site.motion.pause_video', [], 'en'), $this->get('/en')->getContent());
    }

    /**
     * The carousel only exists above three testimonials, so it needs the
     * demo data — DatabaseSeeder deliberately ships none.
     */
    public function test_the_testimonial_carousel_can_be_paused_and_clears_its_timer(): void
    {
        $this->seed(TestimonialSeeder::class);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('x-data="autoAdvance(', $html);
        $this->assertStringContainsString(__('site.motion.pause_slideshow', [], 'ar'), $html);

        // The bare setInterval this replaced could not be stopped, was never
        // cleared, and ignored prefers-reduced-motion.
        $this->assertStringNotContainsString('setInterval', $html);

        // Hover and keyboard focus suspend it.
        $this->assertStringContainsString('@mouseenter="suspend()"', $html);
        $this->assertStringContainsString('@focusin="suspend()"', $html);
    }

    /**
     * Reduced motion is handled in one place for all three, so this asserts
     * the shared module is actually the one shipped rather than each view
     * rolling its own check.
     */
    public function test_reduced_motion_is_handled_by_the_shared_module(): void
    {
        $motion = file_get_contents(resource_path('js/motion.js'));

        $this->assertStringContainsString('prefers-reduced-motion: reduce', $motion);
        $this->assertStringContainsString("Alpine.data('backgroundVideo'", $motion);
        $this->assertStringContainsString("Alpine.data('autoAdvance'", $motion);

        // The carousel must not start at all under reduced motion, and must
        // clear its timer when the component goes away.
        $this->assertStringContainsString('this.stopped = prefersReducedMotion()', $motion);
        $this->assertStringContainsString('destroy()', $motion);
    }

    public function test_the_marquee_still_honours_reduced_motion(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
