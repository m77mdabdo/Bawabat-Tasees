<?php

namespace Tests\Feature\Dashboard;

use Database\Seeders\TrackingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * <x-flash> replaced 19 hand-rolled copies of the same block, which had
 * drifted into three different looks. These tests are what stops it drifting
 * again: every dashboard screen that can flash a message uses the component,
 * every one renders the same brand markup, and nothing renders at all when
 * there is nothing to say.
 */
class FlashComponentTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The Tracking Settings screen reads its six fixed rows by key off a
        // keyed collection, so it needs them to exist. They are seeded by
        // DatabaseSeeder in every real environment; seeding just this one
        // keeps the rest of the cases cheap.
        $this->seed(TrackingSettingSeeder::class);
    }

    /**
     * Every dashboard view that flashes a status, with a route that reaches
     * it. Keeping this list here means a new section that hand-rolls its own
     * block is a failing test, not a silent inconsistency.
     *
     * @return array<string, array{string}>
     */
    public static function flashingScreens(): array
    {
        return [
            'services' => ['dashboard.services.index'],
            'countries' => ['dashboard.countries.index'],
            'faqs' => ['dashboard.faqs.index'],
            'testimonials' => ['dashboard.testimonials.index'],
            'articles' => ['dashboard.articles.index'],
            'campaigns' => ['dashboard.campaigns.index'],
            'media' => ['dashboard.media.index'],
            'pages' => ['dashboard.pages.index'],
            'menu' => ['dashboard.menu.index'],
            'lead sources' => ['dashboard.lead-sources.index'],
            'leads' => ['dashboard.leads.index'],
            'comments' => ['dashboard.comments.index'],
            'contact messages' => ['dashboard.contact-messages.index'],
            'settings' => ['dashboard.settings.edit'],
            'tracking settings' => ['dashboard.tracking-settings.edit'],
        ];
    }

    #[DataProvider('flashingScreens')]
    public function test_a_flashed_status_renders_in_the_brand_style(string $routeName): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->withSession(['status' => 'Saved successfully.'])
            ->get(route($routeName))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Saved successfully.', $html);
        $this->assertStringContainsString('bg-primary-green/5', $html);
        $this->assertStringContainsString('role="status"', $html);
    }

    #[DataProvider('flashingScreens')]
    public function test_nothing_is_rendered_without_a_status(string $routeName): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get(route($routeName))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('role="status"', $html);
    }

    /**
     * The old blocks used bg-green-100/text-green-800 — raw Tailwind on a
     * site with its own green. Those tokens must not come back.
     */
    #[DataProvider('flashingScreens')]
    public function test_the_off_brand_palette_is_gone(string $routeName): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->withSession(['status' => 'Saved successfully.'])
            ->get(route($routeName))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('bg-green-100', $html);
        $this->assertStringNotContainsString('text-green-800', $html);
        $this->assertStringNotContainsString('bg-red-100 text-red-800', $html);
    }

    public function test_a_validation_summary_renders_as_an_assertive_alert(): void
    {
        // A settings save with a malformed URL, followed back to the form
        // so the flashed error bag is what the summary renders from.
        $html = $this->actingAs($this->makeAdmin())
            ->from(route('dashboard.settings.edit'))
            ->followingRedirects()
            ->put(route('dashboard.settings.update'), [
                'settings' => ['social_facebook' => 'not-a-url'],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('border-red-300', $html);
    }

    /**
     * The status message is escaped — it is admin-authored today, but a
     * flash bag is exactly the kind of channel that later carries user
     * input.
     */
    public function test_a_flashed_status_is_escaped(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->withSession(['status' => '<script>alert(1)</script>'])
            ->get(route('dashboard.faqs.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * The two public form confirmations are deliberately NOT this component
     * — they are visually different and each is followed by its own
     * conversion-tracking snippet. Asserted so the exception stays a
     * decision rather than becoming an oversight.
     */
    public function test_the_public_form_confirmations_keep_their_own_markup(): void
    {
        $html = $this->withSession(['status' => 'Thanks, we will be in touch.'])
            ->get(route('contact'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Thanks, we will be in touch.', $html);
        $this->assertStringContainsString('text-center text-primary-green', $html);
    }
}
