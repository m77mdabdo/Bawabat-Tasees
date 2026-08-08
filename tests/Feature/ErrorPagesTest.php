<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_renders_the_branded_page_not_laravels_default(): void
    {
        $response = $this->get('/no-such-page')->assertNotFound();
        $html = $response->getContent();

        $this->assertStringContainsString(__('site.errors.not_found_title', [], 'ar'), $html);
        $this->assertStringContainsString('logo-full-color-256.png', $html);
        $this->assertStringContainsString(__('site.errors.back_home', [], 'ar'), $html);

        // Laravel's bare handler renders exactly "Not Found".
        $this->assertStringNotContainsString('<title>Not Found</title>', $html);
    }

    public function test_404_is_noindexed(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('<meta name="robots" content="noindex">', escape: false);
    }

    public function test_404_offers_a_way_back(): void
    {
        $html = $this->get('/no-such-page')->assertNotFound()->getContent();

        $this->assertStringContainsString('href="'.url('/').'"', $html);
        $this->assertStringContainsString('href="'.url('/contact').'"', $html);
    }

    /**
     * No route matches a 404, so the `setlocale` middleware never runs and
     * the app locale is still the boot-time default. The error views derive
     * the locale from the path instead — without that, an English visitor
     * hitting a bad /en/ URL got an Arabic error page.
     */
    public function test_404_under_the_en_prefix_renders_in_english_and_ltr(): void
    {
        $html = $this->get('/en/no-such-page')->assertNotFound()->getContent();

        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString(__('site.errors.not_found_title', [], 'en'), $html);
        $this->assertStringNotContainsString(__('site.errors.not_found_title', [], 'ar'), $html);
    }

    public function test_arabic_404_renders_in_arabic_and_rtl(): void
    {
        $html = $this->get('/no-such-page')->assertNotFound()->getContent();

        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
    }

    public function test_english_404_links_back_to_the_english_site(): void
    {
        $html = $this->get('/en/no-such-page')->assertNotFound()->getContent();

        $this->assertStringContainsString('href="'.url('/en').'"', $html);
        $this->assertStringContainsString('href="'.url('/en/contact').'"', $html);
    }

    public function test_403_renders_the_branded_page(): void
    {
        // A non-admin hitting the dashboard is the real 403 path.
        $html = $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden()
            ->getContent();

        $this->assertStringContainsString(__('site.errors.forbidden_title', [], 'ar'), $html);
        $this->assertStringContainsString('logo-full-color-256.png', $html);
    }

    public function test_500_renders_the_branded_page(): void
    {
        Route::get('/__boom', fn () => throw new \RuntimeException('boom'))->middleware('web');

        // The handler only renders the 500 view when debug is off;
        // with debug on it shows the Ignition trace instead.
        config(['app.debug' => false]);

        $html = $this->get('/__boom')->assertStatus(500)->getContent();

        $this->assertStringContainsString(__('site.errors.server_error_title', [], 'ar'), $html);
        $this->assertStringContainsString(__('site.errors.back_home', [], 'ar'), $html);
        $this->assertStringNotContainsString('boom', $html, 'The exception message must not leak to visitors.');
    }

    /**
     * The error layout deliberately does not extend layouts/public, whose
     * view composer queries menu_items and settings — a 500 often means the
     * database is exactly what is broken.
     */
    public function test_error_pages_do_not_depend_on_the_public_layout_composer(): void
    {
        $html = $this->get('/no-such-page')->assertNotFound()->getContent();

        // No navbar, no menu-driven markup.
        $this->assertStringNotContainsString('aria-haspopup', $html);
        $this->assertStringNotContainsString('mobileOpen', $html);
    }

    public function test_error_pages_still_carry_the_security_headers(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
