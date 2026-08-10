<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Country;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\SettingsRepository;
use App\Services\Seo\SeoTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The five-level precedence chain that decides the <title>, description and
 * social tags of every public page:
 *
 *   1. the record's own SeoMeta row (admin-authored, per locale)
 *   2. an explicit override passed by the view
 *   3. a value derived from the record's own content
 *   4. the site-wide seo_defaults settings
 *   5. the brand name
 *
 * Each level only shows up when everything above it is empty, which makes it
 * exactly the kind of logic that looks right and is wrong in the fourth case
 * down. It had no direct test until now.
 */
class SeoTagServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeoTagService $seo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seo = app(SeoTagService::class);
    }

    private function withDefaults(?string $title = 'Default Title', ?string $description = 'Default description'): void
    {
        Setting::updateOrCreate(['key' => 'default_meta_title'], ['value' => $title, 'group' => 'seo_defaults']);
        Setting::updateOrCreate(['key' => 'default_meta_description'], ['value' => $description, 'group' => 'seo_defaults']);

        app(SettingsRepository::class)->flush();
    }

    // -----------------------------------------------------------------
    // Precedence, one level at a time
    // -----------------------------------------------------------------

    public function test_level_one_seo_meta_beats_everything(): void
    {
        $this->withDefaults();

        $service = Service::factory()->create(['name' => ['ar' => 'اسم الخدمة', 'en' => 'Service Name']]);
        $service->seoMeta()->create([
            'meta_title' => ['ar' => 'عنوان مخصص', 'en' => 'Curated Title'],
            'meta_description' => ['ar' => 'وصف مخصص', 'en' => 'Curated description'],
        ]);

        $resolved = $this->seo->resolve($service->fresh(), [
            'title' => 'View Override',
            'description' => 'View override description',
        ]);

        $this->assertSame('عنوان مخصص', $resolved['title']);
        $this->assertSame('وصف مخصص', $resolved['description']);
    }

    public function test_level_two_the_view_override_beats_the_records_content(): void
    {
        $this->withDefaults();

        $service = Service::factory()->create([
            'name' => ['ar' => 'اسم الخدمة'],
            'summary' => ['ar' => 'ملخص الخدمة'],
        ]);

        $resolved = $this->seo->resolve($service, [
            'title' => 'View Override',
            'description' => 'View override description',
        ]);

        $this->assertSame('View Override', $resolved['title']);
        $this->assertSame('View override description', $resolved['description']);
    }

    public function test_level_three_the_records_own_content_is_used_when_nothing_above_it_exists(): void
    {
        $this->withDefaults();

        $service = Service::factory()->create([
            'name' => ['ar' => 'اسم الخدمة'],
            'summary' => ['ar' => 'ملخص الخدمة'],
        ]);

        $resolved = $this->seo->resolve($service);

        $this->assertSame('اسم الخدمة', $resolved['title']);
        $this->assertSame('ملخص الخدمة', $resolved['description']);
    }

    public function test_level_four_the_site_defaults_are_used_when_there_is_no_record_at_all(): void
    {
        $this->withDefaults();

        $resolved = $this->seo->resolve();

        $this->assertSame('Default Title', $resolved['title']);
        $this->assertSame('Default description', $resolved['description']);
    }

    /**
     * The reason a tag is never emitted empty. An empty <title> is worse for
     * both a visitor and a crawler than an imperfect one.
     */
    public function test_level_five_the_brand_name_is_the_last_resort(): void
    {
        $this->withDefaults(null, null);

        $resolved = $this->seo->resolve();

        $this->assertSame(__('site.brand.name'), $resolved['title']);
        $this->assertSame(__('site.brand.name'), $resolved['description']);
        $this->assertNotSame('', $resolved['title']);
    }

    /**
     * A blank string must fall through, not win. This is the failure mode the
     * chain is most likely to have: an admin clears a field, the row still
     * exists, and "" is technically a value.
     */
    public function test_a_blank_seo_meta_value_falls_through_instead_of_winning(): void
    {
        $this->withDefaults();

        // summary explicitly null: with it set, level 3 would legitimately
        // answer and the test would never reach the defaults it is about.
        $service = Service::factory()->create([
            'name' => ['ar' => 'اسم الخدمة'],
            'summary' => null,
            'body' => null,
        ]);
        $service->seoMeta()->create([
            'meta_title' => ['ar' => '   '],
            'meta_description' => ['ar' => ''],
        ]);

        $resolved = $this->seo->resolve($service->fresh());

        $this->assertSame('اسم الخدمة', $resolved['title']);
        $this->assertSame('Default description', $resolved['description']);
    }

    // -----------------------------------------------------------------
    // Shape of the output
    // -----------------------------------------------------------------

    public function test_html_never_reaches_a_meta_tag(): void
    {
        $this->withDefaults();

        $article = Article::factory()->create([
            'title' => ['ar' => 'عنوان'],
            'excerpt' => ['ar' => '<p>وصف <strong>مهم</strong></p>'],
        ]);

        $resolved = $this->seo->resolve($article);

        $this->assertStringNotContainsString('<', $resolved['description']);
        $this->assertSame('وصف مهم', $resolved['description']);
    }

    public function test_whitespace_in_content_is_collapsed(): void
    {
        $this->withDefaults();

        $article = Article::factory()->create([
            'title' => ['ar' => 'عنوان'],
            'excerpt' => ['ar' => "line one\n\n   line two"],
        ]);

        $this->assertSame('line one line two', $this->seo->resolve($article)['description']);
    }

    public function test_long_values_are_trimmed_to_the_tag_limits(): void
    {
        $this->withDefaults(str_repeat('a', 300), str_repeat('b', 300));

        $resolved = $this->seo->resolve();

        $this->assertSame(120, mb_strlen($resolved['title']));
        $this->assertSame(160, mb_strlen($resolved['description']));
    }

    public function test_only_articles_are_og_type_article(): void
    {
        $this->assertSame('article', $this->seo->resolve(Article::factory()->create())['og_type']);
        $this->assertSame('website', $this->seo->resolve(Service::factory()->create())['og_type']);
        $this->assertSame('website', $this->seo->resolve()['og_type']);
    }

    public function test_an_explicit_type_override_wins(): void
    {
        $this->assertSame('profile', $this->seo->resolve(null, ['type' => 'profile'])['og_type']);
    }

    public function test_the_og_locale_pair_follows_the_app_locale(): void
    {
        app()->setLocale('ar');
        $ar = $this->seo->resolve();
        $this->assertSame('ar_SA', $ar['og_locale']);
        $this->assertSame('en_US', $ar['og_locale_alternate']);

        app()->setLocale('en');
        $en = $this->seo->resolve();
        $this->assertSame('en_US', $en['og_locale']);
        $this->assertSame('ar_SA', $en['og_locale_alternate']);
    }

    // -----------------------------------------------------------------
    // Canonical and image
    // -----------------------------------------------------------------

    public function test_an_admin_set_canonical_wins_over_the_current_url(): void
    {
        $service = Service::factory()->create();
        $service->seoMeta()->create(['canonical_url' => 'https://example.test/canonical']);

        $this->assertSame('https://example.test/canonical', $this->seo->resolve($service->fresh())['canonical']);
    }

    public function test_the_canonical_defaults_to_the_current_url(): void
    {
        $this->assertSame(url()->current(), $this->seo->resolve()['canonical']);
    }

    public function test_the_seo_meta_image_wins_over_the_records_own(): void
    {
        $service = Service::factory()->create(['cover_image' => 'services/cover.jpg']);
        $service->seoMeta()->create(['og_image' => 'seo/share.jpg']);

        $this->assertStringContainsString('seo/share.jpg', $this->seo->resolve($service->fresh())['og_image']);
    }

    public function test_the_records_own_image_is_used_when_there_is_no_seo_meta_one(): void
    {
        $service = Service::factory()->create(['cover_image' => 'services/cover.jpg']);

        $this->assertStringContainsString('services/cover.jpg', $this->seo->resolve($service)['og_image']);
    }

    public function test_an_absolute_image_url_is_passed_through_untouched(): void
    {
        $service = Service::factory()->create();
        $service->seoMeta()->create(['og_image' => 'https://cdn.example.test/share.png']);

        $this->assertSame('https://cdn.example.test/share.png', $this->seo->resolve($service->fresh())['og_image']);
    }

    /**
     * A shared link is never imageless — and the file it falls back to has to
     * actually exist. It is referenced from PHP rather than from a Blade
     * view, which is exactly how it nearly got deleted as an orphan.
     */
    public function test_the_fallback_share_image_exists_on_disk(): void
    {
        $url = $this->seo->resolve()['og_image'];

        $this->assertNotNull($url);

        $path = public_path(parse_url($url, PHP_URL_PATH));

        $this->assertFileExists($path, 'The og:image fallback points at a file that is not shipped.');
    }

    // -----------------------------------------------------------------
    // Models with no seoMeta relation at all
    // -----------------------------------------------------------------

    /**
     * Faq and Testimonial deliberately have no seoMeta(); the resolver must
     * not assume the relation exists.
     */
    public function test_a_model_without_a_seo_meta_relation_still_resolves(): void
    {
        $this->withDefaults();

        $country = Country::factory()->create([
            'name' => ['ar' => 'الإمارات'],
            'notes' => ['ar' => 'ملاحظات عن الدولة'],
        ]);

        $resolved = $this->seo->resolve($country);

        $this->assertSame('الإمارات', $resolved['title']);
        $this->assertSame('ملاحظات عن الدولة', $resolved['description']);
    }

    public function test_an_eager_loaded_relation_is_used_rather_than_re_queried(): void
    {
        $service = Service::factory()->create();
        SeoMeta::create([
            'seo_metable_type' => Service::class,
            'seo_metable_id' => $service->id,
            'meta_title' => ['ar' => 'من العلاقة'],
        ]);

        $loaded = Service::with('seoMeta')->find($service->id);

        // Warm the settings repository first: resolve() reads seo_defaults
        // through it, and that one query is expected — the claim under test
        // is specifically that seo_meta is not fetched a second time.
        app(SettingsRepository::class)->all();

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $resolved = $this->seo->resolve($loaded);

        $this->assertSame('من العلاقة', $resolved['title']);
        $this->assertEmpty(
            array_filter($queries, fn ($sql) => str_contains($sql, 'seo_meta')),
            'An eager-loaded seoMeta must not be fetched again.'
        );
    }
}
