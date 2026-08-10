<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Before this pass, 26 of 27 images had neither `loading` nor intrinsic
 * dimensions: nothing below the fold was deferred, and every image shifted
 * the layout as it arrived. The two autoplaying background videos had no
 * `preload` either, and the click-to-play tour on /about was fetching 2MB on
 * every visit for a video most people never start.
 *
 * The rules these tests hold in place:
 *   - below-the-fold images are lazy
 *   - above-the-fold artwork is NOT (lazy-loading the LCP element is a
 *     regression dressed as an optimisation)
 *   - static assets carry their real intrinsic dimensions
 */
class MediaLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Every image tag on the page, as a flat list of raw tags.
     *
     * @return array<int, string>
     */
    private function images(string $uri): array
    {
        $html = $this->get($uri)->assertOk()->getContent();

        preg_match_all('/<img\b[^>]*>/s', $html, $matches);

        return $matches[0];
    }

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
            'countries' => ['/countries'],
            'about' => ['/about'],
            'contact' => ['/contact'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_every_image_declares_how_it_loads(string $uri): void
    {
        foreach ($this->images($uri) as $tag) {
            // The header logos are the deliberate exception: above the fold
            // on every page, so they are eager by design and say so with
            // explicit dimensions instead.
            $isHeaderLogo = str_contains($tag, 'logo-full-color-256')
                || str_contains($tag, 'logo-icon-color-128');

            $this->assertTrue(
                str_contains($tag, 'loading=') || $isHeaderLogo || str_contains($tag, 'fetchpriority='),
                "Image declares neither loading nor fetchpriority: {$tag}"
            );
        }
    }

    #[DataProvider('publicPages')]
    public function test_static_artwork_carries_its_intrinsic_dimensions(string $uri): void
    {
        foreach ($this->images($uri) as $tag) {
            // Only assets we ship, whose real size is knowable at author
            // time. An uploaded image's intrinsic size is not, and inventing
            // one would be worse than omitting it.
            if (! preg_match('#/images/(brand|about)/#', $tag) && ! str_contains($tag, '/images/hero-poster')) {
                continue;
            }

            $this->assertStringContainsString('width="', $tag, "No width on a shipped asset: {$tag}");
            $this->assertStringContainsString('height="', $tag, "No height on a shipped asset: {$tag}");
        }
    }

    public function test_the_header_logo_is_never_lazy_loaded(): void
    {
        foreach ($this->images('/') as $tag) {
            if (str_contains($tag, 'hidden sm:block') || str_contains($tag, 'block sm:hidden')) {
                $this->assertStringNotContainsString('loading="lazy"', $tag);
                $this->assertStringContainsString('width="', $tag);
            }
        }
    }

    /**
     * A page hero is the LCP element. Deferring it is the exact opposite of
     * the optimisation being attempted.
     */
    public function test_page_heroes_are_eager_and_high_priority(): void
    {
        // The seeders ship no cover images (they are admin-uploaded), so the
        // hero only exists once there is one to render.
        $service = Service::query()->firstOrFail();
        $service->update(['cover_image' => 'services/cover.jpg']);

        $article = Article::query()->where('is_published', true)->firstOrFail();
        $article->update(['cover_image' => 'articles/cover.jpg']);

        foreach ([
            '/services/'.$service->slug,
            '/articles/'.$article->slug,
        ] as $uri) {
            $hero = collect($this->images($uri))
                ->first(fn ($tag) => str_contains($tag, 'h-full w-full object-cover'));

            $this->assertNotNull($hero, "No hero image found on {$uri}");
            $this->assertStringNotContainsString('loading="lazy"', $hero);
            $this->assertStringContainsString('fetchpriority="high"', $hero);
        }
    }

    public function test_below_the_fold_content_images_are_lazy(): void
    {
        $tags = collect($this->images('/'))
            ->filter(fn ($tag) => str_contains($tag, 'about-team-meeting'));

        $this->assertNotEmpty($tags);

        foreach ($tags as $tag) {
            $this->assertStringContainsString('loading="lazy"', $tag);
        }
    }

    // -----------------------------------------------------------------
    // Video
    // -----------------------------------------------------------------

    public function test_the_autoplaying_backgrounds_do_not_buffer_the_whole_file(): void
    {
        foreach (['/' => 'hero-bg', '/login' => 'login-bg'] as $uri => $asset) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertStringContainsString($asset, $html);
            // "metadata", not "none": these autoplay, so "none" is a hint the
            // browser overrides immediately.
            $this->assertStringContainsString('preload="metadata"', $html);
        }
    }

    public function test_the_click_to_play_tour_is_not_fetched_until_it_is_played(): void
    {
        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringContainsString('about-office-tour.mp4', $html);
        $this->assertStringContainsString('preload="none"', $html);
    }

    /**
     * The 1.4MB vertical cut was referenced nowhere, and eleven logo variants
     * were shipping unused. Deleted — recoverable from git if a future design
     * wants them back.
     */
    /**
     * Scans app/ as well as the views, and that is not incidental: the first
     * cut of this pass deleted logo-full-color-1024.png as an orphan because
     * only Blade had been searched. Its one reference is in
     * SeoTagService::imageUrl(), the og:image fallback — so every shared link
     * on the site would have pointed at a 404.
     */
    public function test_no_unreferenced_media_ships_in_public(): void
    {
        $referenced = array_merge(
            $this->filenamesMentionedIn(resource_path('views')),
            $this->filenamesMentionedIn(app_path()),
            $this->filenamesMentionedIn(base_path('config')),
            $this->filenamesMentionedIn(database_path()),
        );

        $shipped = array_merge(
            $this->filesUnder(public_path('images')),
            $this->filesUnder(public_path('videos')),
        );

        $orphans = array_values(array_diff($shipped, $referenced));

        $this->assertSame([], $orphans, 'Unreferenced media in public/: '.implode(', ', $orphans));
    }

    /**
     * @return array<int, string>
     */
    private function filenamesMentionedIn(string $directory): array
    {
        $found = [];

        foreach ($this->walk($directory) as $file) {
            if (! str_ends_with($file, '.php')) {
                continue;
            }

            preg_match_all('/[\w\-.\/]+\.(?:png|jpg|jpeg|webp|ico|mp4|webm)/', file_get_contents($file), $matches);

            $found = array_merge($found, array_map('basename', $matches[0]));
        }

        return array_unique($found);
    }

    /**
     * @return array<int, string>
     */
    private function filesUnder(string $directory): array
    {
        return array_map('basename', iterator_to_array($this->walk($directory), false));
    }

    /**
     * @return \Generator<int, string>
     */
    private function walk(string $directory): \Generator
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                yield $file->getPathname();
            }
        }
    }
}
