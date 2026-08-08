<?php

namespace Tests\Feature\Dashboard;

use App\Models\Article;
use App\Models\Country;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class SeoMetaControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function articlePayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'welcome-post',
            'title' => ['ar' => 'مرحباً بكم'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'is_published' => '1',
        ], $overrides);
    }

    private function makeArticle(): Article
    {
        return Article::create([
            'slug' => 'welcome-post',
            'title' => ['ar' => 'مرحباً بكم'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    // ---------------------------------------------------------------
    // The form exposes the fields
    // ---------------------------------------------------------------

    #[DataProvider('seoFormRoutes')]
    public function test_seo_fields_appear_on_the_form(string $routeResolver): void
    {
        $admin = $this->makeAdmin();
        $url = $this->{$routeResolver}();

        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertSee('seo[meta_title][ar]', escape: false)
            ->assertSee('seo[meta_description][ar]', escape: false)
            ->assertSee('seo_og_image', escape: false);
    }

    public static function seoFormRoutes(): array
    {
        return [
            'article create' => ['articleCreateUrl'],
            'country create' => ['countryCreateUrl'],
            'service create' => ['serviceCreateUrl'],
            'page edit' => ['pageEditUrl'],
        ];
    }

    private function articleCreateUrl(): string
    {
        return route('dashboard.articles.create');
    }

    private function countryCreateUrl(): string
    {
        return route('dashboard.countries.create');
    }

    private function serviceCreateUrl(): string
    {
        return route('dashboard.services.create');
    }

    private function pageEditUrl(): string
    {
        $page = Page::create([
            'slug' => 'about',
            'title' => ['ar' => 'من نحن'],
            'body' => ['ar' => '<p>نص</p>'],
            'meta_title' => ['ar' => 'من نحن'],
            'meta_description' => ['ar' => 'وصف'],
            'is_published' => true,
        ]);

        return route('dashboard.pages.edit', $page);
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function test_storing_an_article_persists_its_seo_meta(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->articlePayload([
            'seo' => [
                'meta_title' => ['ar' => 'عنوان ميتا', 'en' => 'Meta Title'],
                'meta_description' => ['ar' => 'وصف ميتا', 'en' => 'Meta description'],
                'canonical_url' => 'https://example.com/canonical',
            ],
        ]))->assertRedirect(route('dashboard.articles.index'));

        $seoMeta = Article::firstOrFail()->seoMeta;

        $this->assertNotNull($seoMeta);
        $this->assertSame('عنوان ميتا', $seoMeta->getTranslation('meta_title', 'ar'));
        $this->assertSame('Meta Title', $seoMeta->getTranslation('meta_title', 'en'));
        $this->assertSame('وصف ميتا', $seoMeta->getTranslation('meta_description', 'ar'));
        $this->assertSame('https://example.com/canonical', $seoMeta->canonical_url);
    }

    public function test_no_seo_meta_row_is_created_when_the_fields_are_left_blank(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->articlePayload())
            ->assertRedirect(route('dashboard.articles.index'));

        $this->assertDatabaseCount('seo_meta', 0);
        $this->assertNull(Article::firstOrFail()->seoMeta);
    }

    public function test_blank_locales_are_not_stored_as_empty_strings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->articlePayload([
            'seo' => ['meta_title' => ['ar' => 'عنوان فقط', 'en' => '']],
        ]))->assertRedirect();

        $seoMeta = Article::firstOrFail()->seoMeta;

        $this->assertSame(['ar' => 'عنوان فقط'], $seoMeta->getTranslations('meta_title'));
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function test_updating_an_article_updates_its_existing_seo_meta_row(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle();
        $article->seoMeta()->create(['meta_title' => ['ar' => 'قديم']]);

        $this->actingAs($admin)->put(route('dashboard.articles.update', $article), $this->articlePayload([
            'seo' => ['meta_title' => ['ar' => 'جديد']],
        ]))->assertRedirect(route('dashboard.articles.index'));

        $this->assertDatabaseCount('seo_meta', 1);
        $this->assertSame('جديد', $article->fresh()->seoMeta->getTranslation('meta_title', 'ar'));
    }

    public function test_updating_creates_the_row_when_none_existed(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle();

        $this->actingAs($admin)->put(route('dashboard.articles.update', $article), $this->articlePayload([
            'seo' => ['meta_title' => ['ar' => 'عنوان جديد']],
        ]))->assertRedirect();

        $this->assertSame('عنوان جديد', $article->fresh()->seoMeta->getTranslation('meta_title', 'ar'));
    }

    public function test_og_image_is_uploaded_and_stored(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->articlePayload([
            'seo' => ['meta_title' => ['ar' => 'عنوان']],
            'seo_og_image' => UploadedFile::fake()->image('og.jpg'),
        ]))->assertRedirect();

        $seoMeta = Article::firstOrFail()->seoMeta;

        $this->assertNotNull($seoMeta->og_image);
        Storage::disk('public')->assertExists($seoMeta->og_image);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_english_meta_title_without_arabic_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->articlePayload([
            'seo' => ['meta_title' => ['ar' => '', 'en' => 'English only']],
        ]))->assertSessionHasErrors('seo.meta_title.ar');

        $this->assertDatabaseCount('seo_meta', 0);
    }

    public function test_invalid_canonical_url_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->articlePayload([
            'seo' => ['canonical_url' => 'not-a-url'],
        ]))->assertSessionHasErrors('seo.canonical_url');
    }

    // ---------------------------------------------------------------
    // Other seoMetable resources + cleanup
    // ---------------------------------------------------------------

    public function test_service_and_country_also_persist_seo_meta(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('dashboard.services.store'), [
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس'],
            'summary' => ['ar' => 'ملخص'],
            'body' => ['ar' => '<p>نص</p>'],
            'requirements' => ['ar' => 'متطلبات'],
            'process' => ['ar' => 'خطوات'],
            'is_active' => '1',
            'seo' => ['meta_title' => ['ar' => 'ميتا الخدمة']],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('dashboard.countries.store'), [
            'slug' => 'egypt',
            'name' => ['ar' => 'مصر'],
            'is_active' => '1',
            'seo' => ['meta_title' => ['ar' => 'ميتا الدولة']],
        ])->assertRedirect();

        $this->assertSame('ميتا الخدمة', Service::firstOrFail()->seoMeta->getTranslation('meta_title', 'ar'));
        $this->assertSame('ميتا الدولة', Country::firstOrFail()->seoMeta->getTranslation('meta_title', 'ar'));
    }

    public function test_updating_a_page_persists_seo_meta(): void
    {
        $admin = $this->makeAdmin();
        $page = Page::create([
            'slug' => 'about',
            'title' => ['ar' => 'من نحن'],
            'body' => ['ar' => '<p>نص</p>'],
            'meta_title' => ['ar' => 'من نحن'],
            'meta_description' => ['ar' => 'وصف'],
            'is_published' => true,
        ]);

        $this->actingAs($admin)->put(route('dashboard.pages.update', $page), [
            'title' => ['ar' => 'من نحن'],
            'body' => ['ar' => '<p>نص</p>'],
            'meta_title' => ['ar' => 'من نحن'],
            'meta_description' => ['ar' => 'وصف'],
            'is_published' => '1',
            'seo' => ['meta_title' => ['ar' => 'ميتا الصفحة']],
        ])->assertRedirect();

        $this->assertSame('ميتا الصفحة', $page->fresh()->seoMeta->getTranslation('meta_title', 'ar'));
    }

    /**
     * Country hard-deletes, so its SeoMeta row would be orphaned without
     * the explicit purge — morphOne has no DB-level cascade.
     */
    public function test_deleting_a_country_purges_its_seo_meta(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create(['slug' => 'egypt', 'name' => ['ar' => 'مصر'], 'is_active' => true]);
        $country->seoMeta()->create(['meta_title' => ['ar' => 'ميتا']]);

        $this->assertDatabaseCount('seo_meta', 1);

        $this->actingAs($admin)->delete(route('dashboard.countries.destroy', $country))->assertRedirect();

        $this->assertDatabaseCount('seo_meta', 0);
    }

    public function test_guest_cannot_reach_the_seo_enabled_forms(): void
    {
        $this->get(route('dashboard.articles.create'))->assertRedirect(route('login'));
        $this->post(route('dashboard.articles.store'), $this->articlePayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('seo_meta', 0);
    }

    public function test_seeded_install_gives_pages_and_articles_seo_meta(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, SeoMeta::count());
        $this->assertNotNull(Page::where('slug', 'about')->firstOrFail()->seoMeta);
    }
}
