<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(array $overrides = []): Service
    {
        return Service::create(array_merge([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'ملخص الخدمة'],
            'body' => ['ar' => 'محتوى الخدمة'],
            'requirements' => ['ar' => 'المتطلبات'],
            'process' => ['ar' => 'خطوات العملية'],
            'is_active' => true,
        ], $overrides));
    }

    public function test_arabic_routes_have_no_url_prefix(): void
    {
        $this->assertSame('http://localhost/services', route('services.index'));
    }

    public function test_english_routes_are_prefixed_with_en(): void
    {
        $this->assertSame('http://localhost/en/services', route('services.index.en'));
    }

    public function test_visiting_an_english_route_sets_the_app_locale_to_english(): void
    {
        $this->get('/en');

        $this->assertSame('en', app()->getLocale());
    }

    public function test_visiting_an_arabic_route_sets_the_app_locale_to_arabic(): void
    {
        $this->get('/en');
        $this->get('/');

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_html_dir_is_rtl_for_arabic_and_ltr_for_english(): void
    {
        $this->get('/')->assertSee('dir="rtl"', false);
        $this->get('/en')->assertSee('dir="ltr"', false);
    }

    public function test_english_page_shows_english_brand_name_and_nav_labels(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('Bawabat Taasees Al Sharikat');
        $response->assertSee('Services');
        $response->assertDontSee('بوابة تأسيس الشركات');
    }

    /**
     * The core acceptance criterion: toggling locale must preserve the
     * CURRENT page (e.g. a specific service's detail page), not just
     * bounce back to the homepage — proven for a route with a dynamic
     * slug parameter, not just the static ones.
     */
    public function test_locale_toggle_preserves_the_current_service_page(): void
    {
        $service = $this->makeService();

        $response = $this->get(route('services.show', $service));
        $response->assertOk();
        $response->assertSee(route('services.show.en', $service), false);

        $enResponse = $this->get(route('services.show.en', $service));
        $enResponse->assertOk();
        $enResponse->assertSee(route('services.show', $service), false);
    }

    public function test_hreflang_tags_present_on_home_service_and_article_pages(): void
    {
        $service = $this->makeService();
        $article = Article::create([
            'slug' => 'welcome-post',
            'title' => ['ar' => 'مرحباً بكم'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        foreach ([
            route('home'),
            route('services.show', $service),
            route('articles.show', $article),
        ] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertSee('hreflang="ar"', false);
            $response->assertSee('hreflang="en"', false);
            $response->assertSee('hreflang="x-default"', false);
        }
    }

    /**
     * Proves the translatable fallback locale is genuinely set to 'ar' —
     * a record with no English translation yet still shows its Arabic
     * value when the site is switched to English, not a blank string.
     */
    public function test_translatable_fallback_returns_arabic_when_english_is_missing(): void
    {
        $service = $this->makeService();

        app()->setLocale('en');

        $this->assertSame('تأسيس الشركات', $service->fresh()->name);
    }

    public function test_service_page_shows_arabic_name_over_http_when_locale_is_english_and_no_translation_exists(): void
    {
        $this->makeService();

        $response = $this->get('/en/services');

        $response->assertOk();
        $response->assertSee('تأسيس الشركات');
    }
}
