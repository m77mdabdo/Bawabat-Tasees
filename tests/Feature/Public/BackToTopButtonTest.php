<?php

namespace Tests\Feature\Public;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\MenuItemSeeder;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class BackToTopButtonTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MenuItemSeeder::class);
    }

    private function whatsappConfigured(): void
    {
        Setting::create([
            'key' => 'contact_whatsapp',
            'group' => 'whatsapp',
            'value' => '+966500000000',
        ]);
    }

    // ---------------------------------------------------------------
    // Renders across the public site
    // ---------------------------------------------------------------

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
    public function test_it_renders_on_every_public_page(string $uri): void
    {
        $this->get($uri)
            ->assertOk()
            ->assertSee(__('site.nav.back_to_top'), escape: false);
    }

    /**
     * CMS pages render through the same layout but need a seeded Page
     * row, so they get their own case rather than a 404 in the provider.
     */
    public function test_it_renders_on_a_cms_page(): void
    {
        $this->seed(PageContentSeeder::class);

        $this->get('/about')
            ->assertOk()
            ->assertSee(__('site.nav.back_to_top'), escape: false);
    }

    public function test_it_renders_a_real_button_with_an_accessible_name(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-label="'.preg_quote(__('site.nav.back_to_top'), '/').'"/u',
            $html,
            'Must be a real <button> carrying an aria-label, not a div.'
        );
    }

    public function test_the_label_is_localized(): void
    {
        $this->get('/')->assertOk()->assertSee(__('site.nav.back_to_top', [], 'ar'), escape: false);
        $this->get('/en')->assertOk()->assertSee(__('site.nav.back_to_top', [], 'en'), escape: false);
    }

    // ---------------------------------------------------------------
    // Behaviour
    // ---------------------------------------------------------------

    public function test_it_is_hidden_until_the_user_scrolls(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('window.scrollY > 350', $html);
        // x-cloak keeps it invisible before Alpine boots.
        $this->assertStringContainsString('x-cloak', $html);
    }

    public function test_it_smooth_scrolls_to_the_top(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('window.scrollTo({ top: 0, behavior:', $html);
        $this->assertStringContainsString("'smooth'", $html);
    }

    public function test_it_respects_prefers_reduced_motion(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('prefers-reduced-motion: reduce', $html);
        $this->assertStringContainsString("'auto'", $html);
    }

    public function test_it_uses_enter_and_leave_transitions(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('x-transition:enter', $html);
        $this->assertStringContainsString('x-transition:leave', $html);
    }

    // ---------------------------------------------------------------
    // Positioning relative to the WhatsApp button
    // ---------------------------------------------------------------

    /**
     * Both floats are pinned to the same PHYSICAL corner on purpose (see
     * the component docblock) — using the logical start-6 here would put
     * this button on the opposite side from WhatsApp in Arabic.
     */
    public function test_it_shares_the_whatsapp_buttons_corner_in_both_locales(): void
    {
        $this->whatsappConfigured();

        foreach (['/', '/en'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertStringContainsString('fixed bottom-24 left-6', $html, "Back-to-top misplaced on {$uri}");
            $this->assertStringContainsString('fixed bottom-6 left-6', $html, "WhatsApp misplaced on {$uri}");
        }
    }

    public function test_it_is_lifted_clear_of_the_whatsapp_button(): void
    {
        $this->whatsappConfigured();

        $html = $this->get('/')->assertOk()->getContent();

        // WhatsApp sits at bottom-6 (24px, 56px tall). bottom-24 (96px)
        // clears it with a 16px gap.
        $this->assertStringContainsString('bottom-24', $html);
    }

    public function test_it_drops_down_when_there_is_no_whatsapp_button(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('wa.me/', $html, 'No WhatsApp number is configured here.');
        $this->assertStringContainsString('fixed bottom-6 left-6', $html, 'With nothing to clear it should sit low.');
        $this->assertStringNotContainsString('bottom-24', $html);
    }

    public function test_it_matches_the_whatsapp_buttons_size_and_shape(): void
    {
        $this->whatsappConfigured();

        $html = $this->get('/')->assertOk()->getContent();

        // 56px circle, same as the sibling — and well past the 44px
        // touch-target minimum. Counted on the float-button class run
        // itself: shadow-lg alone also appears on the hero CTAs.
        $this->assertSame(2, substr_count($html, 'h-14 w-14 items-center justify-center rounded-full'));
        // Counted on the fixed-position float class run, not on a bare
        // 'rounded-full bg-' — the hero's motion toggle is also a filled
        // circle and would otherwise be counted as a third float.
        $this->assertSame(2, substr_count($html, 'z-30 flex h-14 w-14 items-center justify-center rounded-full bg-'), 'Both floats are filled circles.');
    }

    public function test_it_uses_the_brand_palette_and_has_a_visible_focus_state(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('bg-primary-green', $html);
        $this->assertStringContainsString('focus-visible:ring-2', $html);
    }

    // ---------------------------------------------------------------
    // Must NOT appear outside the public site
    // ---------------------------------------------------------------

    public function test_it_does_not_appear_on_dashboard_pages(): void
    {
        $admin = $this->makeAdmin();

        foreach (['/dashboard', '/dashboard/leads', '/dashboard/settings'] as $uri) {
            $this->actingAs($admin)->get($uri)
                ->assertOk()
                ->assertDontSee(__('site.nav.back_to_top'), escape: false);
        }
    }

    public function test_it_does_not_appear_on_auth_pages(): void
    {
        $this->get('/login')->assertOk()->assertDontSee(__('site.nav.back_to_top'), escape: false);
        $this->get('/forgot-password')->assertOk()->assertDontSee(__('site.nav.back_to_top'), escape: false);
    }

    public function test_it_does_not_appear_on_the_profile_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertDontSee(__('site.nav.back_to_top'), escape: false);
    }

    public function test_it_does_not_appear_on_error_pages(): void
    {
        // The error layout deliberately does not extend layouts/public.
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertDontSee(__('site.nav.back_to_top'), escape: false);
    }
}
