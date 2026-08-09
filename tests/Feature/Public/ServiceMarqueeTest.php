<?php

namespace Tests\Feature\Public;

use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The homepage services teaser is an auto-scrolling marquee. The motion
 * itself is CSS, which a headless test cannot observe — what it can
 * pin is the structure the CSS depends on, and the structural invariant
 * that makes the loop seamless: the track is exactly two identical
 * halves, so the frame at 100% is the frame at 0%.
 */
class ServiceMarqueeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function marquee(string $uri): string
    {
        $html = $this->get($uri)->assertOk()->getContent();

        preg_match('/<div\s+class="bt-marquee".*?<\/div>\s*<\/div>\s*<\/div>/s', $html, $m);
        $this->assertNotEmpty($m, "No marquee rendered on {$uri}.");

        return $m[0];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function locales(): array
    {
        return [
            // Arabic flows right-to-left, so the cards travel left.
            'arabic' => ['/', 'rtl', 'ar'],
            // English flows the opposite way.
            'english' => ['/en', 'ltr', 'en'],
        ];
    }

    // ---------------------------------------------------------------
    // Direction hook
    // ---------------------------------------------------------------

    #[DataProvider('locales')]
    public function test_the_marquee_renders_with_the_direction_hook_for_its_locale(string $uri, string $dir): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('class="bt-marquee"', $html);
        $this->assertStringContainsString('data-marquee-direction="'.$dir.'"', $html);
    }

    /**
     * The two locales must not resolve to the same direction — that is
     * the whole point, and a wrong ternary would still render a valid
     * marquee in both.
     */
    public function test_the_two_locales_scroll_in_opposite_directions(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-marquee-direction="rtl"', escape: false)
            ->assertDontSee('data-marquee-direction="ltr"', escape: false);

        $this->get('/en')->assertOk()
            ->assertSee('data-marquee-direction="ltr"', escape: false)
            ->assertDontSee('data-marquee-direction="rtl"', escape: false);
    }

    #[DataProvider('locales')]
    public function test_the_cards_carry_their_own_text_direction(string $uri, string $dir): void
    {
        // The viewport is forced to direction:ltr so the transform
        // geometry is identical in both locales, so each card has to
        // restore its own direction for its text.
        $this->assertStringContainsString('dir="'.$dir.'"', $this->marquee($uri));
    }

    // ---------------------------------------------------------------
    // Seamless loop
    // ---------------------------------------------------------------

    /**
     * THE INVARIANT. The animation translates the track by 50% of its
     * own width. That is only seamless if the second half is identical
     * to the first — otherwise the loop visibly jumps once per cycle.
     */
    #[DataProvider('locales')]
    public function test_the_track_is_two_identical_halves(string $uri): void
    {
        $marquee = $this->marquee($uri);

        preg_match_all('/<article\s+class="bt-marquee__item[^"]*"/', $marquee, $items);
        $count = count($items[0]);

        $this->assertGreaterThan(0, $count);
        $this->assertSame(0, $count % 2, 'An odd number of cards cannot split into two equal halves.');

        // Compare the rendered service links, which is what a viewer
        // actually sees repeat.
        preg_match_all('/href="[^"]*\/services\/([^"]+)"/', $marquee, $links);
        $slugs = $links[1];
        $half = count($slugs) / 2;

        $this->assertSame(
            array_slice($slugs, 0, $half),
            array_values(array_slice($slugs, $half)),
            'The two halves of the track differ, so the loop would jump.'
        );
    }

    /**
     * A unit narrower than the viewport would leave a visible gap at
     * the far edge for part of every cycle, so a short service list
     * repeats until it is wide enough.
     */
    public function test_a_short_service_list_is_repeated_until_it_can_fill_the_row(): void
    {
        Service::query()->delete();
        Service::factory()->count(2)->create(['is_active' => true]);

        $marquee = $this->marquee('/');

        preg_match_all('/<article\s+class="bt-marquee__item/', $marquee, $items);

        // 2 services → 3 repeats per unit → 6 per unit → 12 in the track.
        $this->assertSame(12, count($items[0]));
    }

    public function test_the_duration_scales_with_the_card_count_so_the_speed_is_constant(): void
    {
        $this->assertStringContainsString('--bt-marquee-duration: 48s', $this->marquee('/'));

        Service::query()->delete();
        Service::factory()->count(3)->create(['is_active' => true]);

        // 3 services → 2 repeats → 6 cards per unit → the same 48s, so
        // the pixels-per-second does not change with the catalogue size.
        $this->assertStringContainsString('--bt-marquee-duration: 48s', $this->marquee('/'));
    }

    // ---------------------------------------------------------------
    // Cards stay working links
    // ---------------------------------------------------------------

    #[DataProvider('locales')]
    public function test_every_real_card_is_a_working_link_to_its_service(string $uri, string $dir, string $locale): void
    {
        $marquee = $this->marquee($uri);

        foreach (Service::active()->orderByDesc('is_flagship')->orderBy('sort_order')->take(6)->get() as $service) {
            $expected = $locale === 'ar'
                ? route('services.show', $service)
                : route('services.show.en', $service);

            $this->assertStringContainsString('href="'.$expected.'"', $marquee);
        }
    }

    /**
     * The duplicate half exists only to close the loop. Left reachable,
     * a keyboard user would walk the same six services twice and a
     * screen reader would announce every service twice.
     */
    #[DataProvider('locales')]
    public function test_the_duplicated_half_is_hidden_from_assistive_tech_and_the_tab_order(string $uri): void
    {
        $marquee = $this->marquee($uri);

        preg_match_all('/<article\s+class="bt-marquee__item([^"]*)"([^>]*)>/', $marquee, $articles, PREG_SET_ORDER);

        $clones = array_filter($articles, fn ($a) => str_contains($a[1], 'bt-marquee__clone'));
        $reals = array_filter($articles, fn ($a) => ! str_contains($a[1], 'bt-marquee__clone'));

        $this->assertNotEmpty($clones);
        $this->assertNotEmpty($reals);

        foreach ($clones as $clone) {
            $this->assertStringContainsString('aria-hidden="true"', $clone[2]);
        }

        foreach ($reals as $real) {
            $this->assertStringNotContainsString('aria-hidden', $real[2]);
        }

        // Three links per card, and every link in a clone is skipped.
        $this->assertSame(count($clones) * 3, substr_count($marquee, 'tabindex="-1"'));
    }

    // ---------------------------------------------------------------
    // Pause and reduced motion
    // ---------------------------------------------------------------

    public function test_touch_holds_the_animation_so_a_swipe_does_not_fight_it(): void
    {
        $marquee = $this->marquee('/');

        // Pointer and keyboard pausing is :hover / :focus-within in CSS;
        // a touch screen has neither, so it needs a real handler.
        $this->assertStringContainsString('@touchstart.passive="held = true"', $marquee);
        $this->assertStringContainsString('@touchend.window="held = false"', $marquee);
        $this->assertStringContainsString(':data-paused="held ? \'true\' : \'false\'"', $marquee);
    }

    /**
     * The CSS carries the pause and reduced-motion rules, so assert on
     * the built stylesheet rather than trusting that the class names in
     * the markup mean anything.
     */
    public function test_the_stylesheet_pauses_on_hover_and_focus_and_honours_reduced_motion(): void
    {
        $css = $this->builtCss();

        $this->assertStringContainsString('.bt-marquee:hover .bt-marquee__track', $css);
        $this->assertStringContainsString('.bt-marquee:focus-within .bt-marquee__track', $css);
        $this->assertStringContainsString('animation-play-state:paused', $css);

        $this->assertMatchesRegularExpression(
            '/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{[^}]*\.bt-marquee__track\{animation:none\}/',
            $css,
            'Reduced motion must switch the animation off entirely.'
        );
    }

    public function test_each_locale_gets_its_own_keyframes(): void
    {
        $css = $this->builtCss();

        $this->assertStringContainsString('@keyframes bt-marquee-left', $css);
        $this->assertStringContainsString('@keyframes bt-marquee-right', $css);
        // The minifier drops the quotes from attribute selectors, so
        // this matches on the pairing rather than on a literal string.
        $this->assertMatchesRegularExpression(
            '/\[data-marquee-direction=[\'"]?rtl[\'"]?\]\s*\.bt-marquee__track\{animation-name:bt-marquee-left\}/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\[data-marquee-direction=[\'"]?ltr[\'"]?\]\s*\.bt-marquee__track\{animation-name:bt-marquee-right\}/',
            $css
        );
    }

    /**
     * The subtlest part of the whole thing.
     *
     * `gap` sits BETWEEN items, so a track of 2N items has 2N-1 gaps.
     * Its width is 2N·C + (2N-1)·G, while one unit's true pitch is
     * N·(C+G) — so translating by 50% falls half a gap short and the
     * loop jumps every cycle (12px at a 24px gap: a stutter rather than
     * an obvious break, which is harder to notice and harder to
     * diagnose). Spacing has to belong to the item.
     */
    public function test_the_track_spaces_cards_by_item_margin_not_by_gap(): void
    {
        $css = $this->builtCss();

        preg_match('/\.bt-marquee__track\{([^}]*)\}/', $css, $track);
        $this->assertNotEmpty($track);
        $this->assertStringNotContainsString(
            'gap:',
            $track[1],
            'A gap on the track makes 50% of its width fall half a gap short of one unit, so the loop stutters.'
        );

        preg_match('/\.bt-marquee__item\{([^}]*)\}/', $css, $item);
        $this->assertNotEmpty($item);
        $this->assertStringContainsString('margin-right:', $item[1]);
        // Physical, because the cards carry their own dir and
        // margin-inline-end would flip to the wrong side in Arabic.
        $this->assertStringNotContainsString('margin-inline', $item[1]);
    }

    /**
     * A positive translate inside overflow-x:auto creates real
     * scrollable overflow, which would have given Arabic a strip of
     * empty space that English does not have. Both directions stay
     * within [-50%, 0].
     */
    public function test_neither_direction_uses_a_positive_translate(): void
    {
        $css = $this->builtCss();

        preg_match_all('/@keyframes bt-marquee-(left|right)\{([^@]*?)\}\s*\}/s', $css, $frames, PREG_SET_ORDER);
        $this->assertCount(2, $frames);

        foreach ($frames as $frame) {
            $this->assertDoesNotMatchRegularExpression(
                '/translate3d\(\s*(?!-)[1-9]/',
                $frame[2],
                "bt-marquee-{$frame[1]} translates in the positive direction."
            );
        }
    }

    // ---------------------------------------------------------------
    // Nothing else moved
    // ---------------------------------------------------------------

    public function test_the_section_heading_and_cta_are_unchanged(): void
    {
        foreach (['/' => 'ar', '/en' => 'en'] as $uri => $locale) {
            $this->get($uri)->assertOk()
                ->assertSee(__('site.home.services_heading', [], $locale), escape: false)
                ->assertSee(__('site.home.services_eyebrow', [], $locale), escape: false)
                ->assertSee(__('site.home.services_cta', [], $locale), escape: false);
        }
    }

    public function test_the_services_index_still_renders_a_static_grid(): void
    {
        foreach (['/services', '/en/services'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertStringNotContainsString('bt-marquee', $html, "{$uri} must not become a marquee.");
            $this->assertStringContainsString('grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3', $html);
        }
    }

    public function test_no_other_homepage_section_became_a_marquee(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="bt-marquee"'));
    }

    public function test_the_section_is_still_hidden_when_there_are_no_services(): void
    {
        Service::query()->delete();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('bt-marquee', $html);
        $this->assertStringNotContainsString(__('site.home.services_heading'), $html);
    }

    private function builtCss(): string
    {
        $files = glob(public_path('build/assets/*.css'));
        $this->assertNotEmpty($files, 'Run `npm run build` — the marquee is CSS-driven.');

        return file_get_contents($files[0]);
    }
}
