<?php

namespace Tests\Feature\Public;

use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Three homepage sections are auto-scrolling marquees driven by one
 * shared component and one set of keyframes. The motion itself is CSS,
 * which a headless test cannot observe — what it can pin is the
 * structure the CSS depends on, and the structural invariant that makes
 * the loop seamless: the track is exactly two identical halves, so the
 * frame at 100% is the frame at 0%.
 */
class MarqueeTest extends TestCase
{
    use RefreshDatabase;

    /** Section order down the homepage. */
    private const SERVICES = 0;

    private const WHY_INVEST = 1;

    private const STEPS = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Slices out one marquee by position rather than by matching a
     * closing-tag shape — the cards nest divs, so any "up to the third
     * </div>" pattern is a coin flip.
     *
     * @return array<int, string>
     */
    private function marquees(string $uri): array
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $parts = preg_split('/(?=<div\s+class="bt-marquee")/', $html);

        // Matched with a regex, not str_starts_with: Blade renders the
        // root element across several lines, so the attribute is not a
        // single space after the tag name.
        $found = array_values(array_filter(
            $parts,
            fn ($p) => (bool) preg_match('/^<div\s+class="bt-marquee"/', $p)
        ));

        // Each slice runs to the next marquee — or, for the last one, to
        // the end of the document, which would drag in the FAQ and
        // article headings below it. Every marquee sits inside its own
        // <section> and contains none, so that closing tag is the
        // reliable end marker.
        return array_map(
            fn ($p) => ($end = strpos($p, '</section>')) === false ? $p : substr($p, 0, $end),
            $found
        );
    }

    private function marquee(string $uri, int $index = self::SERVICES): string
    {
        $marquees = $this->marquees($uri);

        $this->assertArrayHasKey($index, $marquees, "No marquee #{$index} on {$uri}.");

        return $marquees[$index];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'arabic' => ['/', 'ar'],
            'english' => ['/en', 'en'],
        ];
    }

    /**
     * @return array<string, array{int, string, string, string}>
     */
    public static function sections(): array
    {
        // index, flow, direction in Arabic, direction in English
        return [
            'services' => [self::SERVICES, 'base', 'rtl', 'ltr'],
            'why-invest' => [self::WHY_INVEST, 'reverse', 'ltr', 'rtl'],
            'formation steps' => [self::STEPS, 'base', 'rtl', 'ltr'],
        ];
    }

    // ---------------------------------------------------------------
    // All three sections are marquees
    // ---------------------------------------------------------------

    #[DataProvider('locales')]
    public function test_three_homepage_sections_render_a_marquee(string $uri): void
    {
        $this->assertCount(3, $this->marquees($uri));
    }

    #[DataProvider('sections')]
    public function test_each_section_carries_its_own_base_direction(int $index, string $flow, string $ar, string $en): void
    {
        foreach ([['/', $ar], ['/en', $en]] as [$uri, $expected]) {
            $marquee = $this->marquee($uri, $index);

            $this->assertStringContainsString('data-marquee-flow="'.$flow.'"', $marquee);
            $this->assertStringContainsString('data-marquee-direction="'.$expected.'"', $marquee);
        }
    }

    // ---------------------------------------------------------------
    // Alternation — the actual point of this change
    // ---------------------------------------------------------------

    /**
     * A / B / A down the page. Asserted as a sequence rather than
     * per-section, because the failure that matters is two adjacent
     * rows drifting the same way, which per-section checks would miss
     * if both were wrong together.
     */
    #[DataProvider('locales')]
    public function test_directions_alternate_down_the_page(string $uri): void
    {
        $directions = array_map(
            function (string $marquee) {
                preg_match('/data-marquee-direction="([a-z]+)"/', $marquee, $m);

                return $m[1];
            },
            $this->marquees($uri)
        );

        $this->assertCount(3, $directions);

        foreach ([0, 1] as $i) {
            $this->assertNotSame(
                $directions[$i],
                $directions[$i + 1],
                "Marquee {$i} and ".($i + 1).' drift the same way, so they do not alternate.'
            );
        }

        // …and the third returns to the first, so the pattern is A/B/A.
        $this->assertSame($directions[0], $directions[2]);
    }

    /**
     * The whole set flips with the locale — not just the first one.
     */
    public function test_the_whole_pattern_flips_when_the_locale_flips(): void
    {
        $ar = array_map(fn ($m) => preg_match('/data-marquee-direction="([a-z]+)"/', $m, $x) ? $x[1] : null, $this->marquees('/'));
        $en = array_map(fn ($m) => preg_match('/data-marquee-direction="([a-z]+)"/', $m, $x) ? $x[1] : null, $this->marquees('/en'));

        $this->assertSame(['rtl', 'ltr', 'rtl'], $ar);
        $this->assertSame(['ltr', 'rtl', 'ltr'], $en);
    }

    #[DataProvider('sections')]
    public function test_the_cards_carry_the_reading_direction_not_the_flow_direction(int $index): void
    {
        // The viewport is forced to direction:ltr so the transform
        // geometry is identical everywhere, so each card restores the
        // locale's reading direction for its text — which is the same
        // in every section regardless of which way that section drifts.
        $this->assertStringContainsString('dir="rtl"', $this->marquee('/', $index));
        $this->assertStringContainsString('dir="ltr"', $this->marquee('/en', $index));
    }

    // ---------------------------------------------------------------
    // Seamless loop, for every section
    // ---------------------------------------------------------------

    /**
     * THE INVARIANT. The animation translates the track by 50% of its
     * own width. That is only seamless if the second half is identical
     * to the first — otherwise the loop visibly jumps once per cycle.
     */
    #[DataProvider('sections')]
    public function test_every_track_is_two_identical_halves(int $index): void
    {
        foreach (['/', '/en'] as $uri) {
            $marquee = $this->marquee($uri, $index);

            preg_match_all('/<article\s+class="bt-marquee__item[^"]*"/', $marquee, $items);
            $count = count($items[0]);

            $this->assertGreaterThan(0, $count, "Marquee {$index} on {$uri} rendered no cards.");
            $this->assertSame(0, $count % 2, 'An odd number of cards cannot split into two equal halves.');

            // Compare the rendered card headings, which is what a viewer
            // actually sees repeat.
            preg_match_all('/<h3[^>]*>(?:\s*<a[^>]*>)?\s*(.*?)\s*(?:<\/a>)?\s*<\/h3>/s', $marquee, $titles);
            $seen = $titles[1];
            $half = intdiv(count($seen), 2);

            $this->assertGreaterThan(0, $half);
            $this->assertSame(
                array_slice($seen, 0, $half),
                array_values(array_slice($seen, $half)),
                "Marquee {$index} on {$uri}: the halves differ, so the loop would jump."
            );
        }
    }

    /**
     * A unit narrower than the viewport would leave a visible gap at the
     * far edge for part of every cycle, so a short set repeats until it
     * is wide enough. Why-invest ships four cards and is the section
     * that actually exercises this.
     */
    #[DataProvider('sections')]
    public function test_every_unit_is_wide_enough_to_fill_the_row(int $index): void
    {
        $marquee = $this->marquee('/', $index);

        preg_match_all('/<article\s+class="bt-marquee__item([^"]*)"/', $marquee, $items);
        $total = count($items[0]);
        $compact = str_contains($items[1][0] ?? '', 'bt-marquee__item--compact');

        $pitch = $compact ? 264 : 360;
        $unitWidth = ($total / 2) * $pitch;

        $this->assertGreaterThanOrEqual(
            1280,
            $unitWidth,
            "Marquee {$index}: one unit is {$unitWidth}px, narrower than the 1280px container, so the far edge would go empty."
        );
    }

    public function test_a_short_service_list_is_repeated_until_it_can_fill_the_row(): void
    {
        Service::query()->delete();
        Service::factory()->count(2)->create(['is_active' => true]);

        $marquee = $this->marquee('/', self::SERVICES);

        preg_match_all('/<article\s+class="bt-marquee__item/', $marquee, $items);

        // 2 services → 3 passes per unit → 6 per unit → 12 in the track.
        $this->assertSame(12, count($items[0]));
    }

    /**
     * Three ribbons are visible at once, so a shared pixels-per-second
     * is what stops them reading as unrelated widgets at random rates.
     */
    #[DataProvider('sections')]
    public function test_every_marquee_moves_at_the_same_speed(int $index): void
    {
        $marquee = $this->marquee('/', $index);

        preg_match('/--bt-marquee-duration: (\d+)s/', $marquee, $d);
        $this->assertNotEmpty($d, "Marquee {$index} has no duration.");

        preg_match_all('/<article\s+class="bt-marquee__item([^"]*)"/', $marquee, $items);
        $compact = str_contains($items[1][0] ?? '', 'bt-marquee__item--compact');
        $pitch = $compact ? 264 : 360;
        $unitCards = count($items[0]) / 2;

        $speed = ($unitCards * $pitch) / (int) $d[1];

        // 45px/s, allowing for the duration being rounded to whole seconds.
        $this->assertEqualsWithDelta(45, $speed, 1.5, "Marquee {$index} drifts at {$speed}px/s.");
    }

    // ---------------------------------------------------------------
    // Cards and accessibility
    // ---------------------------------------------------------------

    #[DataProvider('locales')]
    public function test_every_real_service_card_is_a_working_link(string $uri, string $locale): void
    {
        $marquee = $this->marquee($uri, self::SERVICES);

        foreach (Service::active()->orderByDesc('is_flagship')->orderBy('sort_order')->take(6)->get() as $service) {
            $expected = $locale === 'ar'
                ? route('services.show', $service)
                : route('services.show.en', $service);

            $this->assertStringContainsString('href="'.$expected.'"', $marquee);
        }
    }

    /**
     * The duplicate copies exist only to close the loop. Left reachable,
     * a keyboard user would walk the same cards several times and a
     * screen reader would announce them several times.
     */
    #[DataProvider('sections')]
    public function test_every_duplicate_is_hidden_from_assistive_tech(int $index): void
    {
        $marquee = $this->marquee('/', $index);

        preg_match_all('/<article\s+class="bt-marquee__item([^"]*)"([^>]*)>/', $marquee, $articles, PREG_SET_ORDER);

        $clones = array_filter($articles, fn ($a) => str_contains($a[1], 'bt-marquee__clone'));
        $reals = array_filter($articles, fn ($a) => ! str_contains($a[1], 'bt-marquee__clone'));

        $this->assertNotEmpty($clones, "Marquee {$index} has no duplicates, so it cannot loop.");
        $this->assertNotEmpty($reals);

        foreach ($clones as $clone) {
            $this->assertStringContainsString('aria-hidden="true"', $clone[2]);
        }

        foreach ($reals as $real) {
            $this->assertStringNotContainsString('aria-hidden', $real[2]);
        }
    }

    public function test_duplicate_service_links_are_out_of_the_tab_order(): void
    {
        $marquee = $this->marquee('/', self::SERVICES);

        preg_match_all('/<article\s+class="bt-marquee__item([^"]*)"/', $marquee, $items);
        $clones = count(array_filter($items[1], fn ($c) => str_contains($c, 'clone')));

        // Three links per service card.
        $this->assertSame($clones * 3, substr_count($marquee, 'tabindex="-1"'));
    }

    /**
     * The step number comes from the position in the ORIGINAL set. Using
     * the loop position would have the duplicates counting on past the
     * real total — step 8 of 7.
     */
    public function test_numbered_steps_restart_in_the_duplicate_half(): void
    {
        $marquee = $this->marquee('/', self::STEPS);

        preg_match_all('/rounded-full bg-primary-green[^>]*>\s*(\d+)/', $marquee, $numbers);
        $seen = array_map('intval', $numbers[1]);

        $this->assertNotEmpty($seen);
        $half = intdiv(count($seen), 2);

        $this->assertSame(array_slice($seen, 0, $half), array_values(array_slice($seen, $half)));
        $this->assertSame(1, $seen[0]);
        $this->assertSame(1, $seen[$half], 'The duplicate half must restart at 1, not continue counting.');

        // Bounded by the number of REAL steps, not by half the track: a
        // short list repeats within a unit too, so half can be several
        // times the real count.
        $realSteps = count(array_unique($seen));
        $this->assertSame(
            range(1, $realSteps),
            array_values(array_unique($seen)),
            'Step numbers must run 1..n over the real steps and restart, never continue past n.'
        );
        $this->assertSame($realSteps, max($seen), 'No step may be numbered past the real total.');
    }

    // ---------------------------------------------------------------
    // Pause, reduced motion, geometry — shared CSS, asserted once
    // ---------------------------------------------------------------

    #[DataProvider('sections')]
    public function test_touch_holds_every_marquee_so_a_swipe_does_not_fight_it(int $index): void
    {
        $marquee = $this->marquee('/', $index);

        // Pointer and keyboard pausing is :hover / :focus-within in CSS;
        // a touch screen has neither, so it needs a real handler.
        $this->assertStringContainsString('@touchstart.passive="held = true"', $marquee);
        $this->assertStringContainsString('@touchend.window="held = false"', $marquee);
        $this->assertStringContainsString(':data-paused="held ? \'true\' : \'false\'"', $marquee);
    }

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

    public function test_one_set_of_keyframes_serves_every_section(): void
    {
        $css = $this->builtCss();

        // Two keyframe blocks total, not two per section — the whole
        // point of generalising the component.
        $this->assertSame(1, substr_count($css, '@keyframes bt-marquee-left'));
        $this->assertSame(1, substr_count($css, '@keyframes bt-marquee-right'));

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
     * `gap` sits BETWEEN items, so a track of 2N items has 2N-1 gaps.
     * Its width is 2N·C + (2N-1)·G, while one unit's true pitch is
     * N·(C+G) — so translating by 50% falls half a gap short and the
     * loop jumps every cycle. Spacing has to belong to the item.
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
     * The compact size must not carry its own margin: a different pitch
     * is fine, a different margin inside the same track is not.
     */
    public function test_the_compact_size_only_changes_width(): void
    {
        $css = $this->builtCss();

        preg_match_all('/\.bt-marquee__item--compact\{([^}]*)\}/', $css, $rules);
        $this->assertNotEmpty($rules[1]);

        foreach ($rules[1] as $rule) {
            $this->assertMatchesRegularExpression('/^width:[^;]+;?$/', $rule, "Compact overrode more than width: {$rule}");
        }
    }

    /**
     * A positive translate inside overflow-x:auto creates real
     * scrollable overflow, which would have given Arabic a strip of
     * empty space that English does not have.
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

    public function test_every_heading_eyebrow_and_cta_is_unchanged(): void
    {
        foreach (['/' => 'ar', '/en' => 'en'] as $uri => $locale) {
            $response = $this->get($uri)->assertOk();

            foreach (['services', 'why_invest', 'formation'] as $section) {
                foreach (['eyebrow', 'heading', 'cta'] as $part) {
                    $response->assertSee(__("site.home.{$section}_{$part}", [], $locale), escape: false);
                }
            }
        }
    }

    public function test_the_inner_pages_are_untouched(): void
    {
        foreach ([
            '/services', '/en/services',
            '/why-invest', '/en/why-invest',
            '/formation-process', '/en/formation-process',
        ] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertStringNotContainsString('bt-marquee', $html, "{$uri} must not become a marquee.");
        }
    }

    public function test_the_services_index_still_renders_a_static_grid(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3', $html);
    }

    public function test_no_other_homepage_section_became_a_marquee(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Testimonials keep their own fade carousel; countries, FAQs and
        // articles keep their grids.
        $this->assertSame(3, substr_count($html, 'class="bt-marquee"'));
    }

    public function test_each_section_still_disappears_when_it_has_no_cards(): void
    {
        Service::query()->delete();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="bt-marquee"'));
        $this->assertStringNotContainsString(__('site.home.services_heading'), $html);
    }

    private function builtCss(): string
    {
        $files = glob(public_path('build/assets/*.css'));
        $this->assertNotEmpty($files, 'Run `npm run build` — the marquee is CSS-driven.');

        return file_get_contents($files[0]);
    }
}
