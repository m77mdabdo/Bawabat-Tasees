<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\TrackingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_real_hero_and_video_and_no_laravel_branding(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('أسس شركتك في السعودية بثقة');
        $response->assertSee('hero-bg.mp4', false);
        $response->assertDontSee('Laravel', false);
    }

    /**
     * A fresh install has zero services/testimonials/articles/why-invest
     * or formation-process pages — every data-driven section must hide
     * itself cleanly rather than render an empty-looking block, while the
     * hero and the always-on final CTA band still show.
     */
    public function test_home_page_hides_data_driven_sections_when_no_real_data_exists(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('كل ما تحتاجه لتأسيس شركتك');
        $response->assertDontSee('لماذا تستثمر في السعودية');
        $response->assertDontSee('رحلتك نحو تأسيس شركتك');
        $response->assertDontSee('ماذا يقول عملاؤنا');
        $response->assertDontSee('أحدث المقالات');
        $response->assertSee('جاهز لتأسيس شركتك في السعودية؟');
    }

    public function test_home_page_shows_services_section_when_active_services_exist(): void
    {
        Service::create([
            'slug' => 'company-formation', 'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'ملخص'], 'body' => ['ar' => 'محتوى'],
            'requirements' => ['ar' => 'متطلبات'], 'process' => ['ar' => 'خطوات'],
            'is_active' => true, 'is_flagship' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('كل ما تحتاجه لتأسيس شركتك');
        $response->assertSee('تأسيس الشركات');
        $response->assertSee('خدمة رئيسية');
    }

    public function test_home_page_shows_why_invest_and_formation_process_sections_when_pages_exist(): void
    {
        $whyInvest = Page::create([
            'slug' => 'why-invest-saudi-arabia', 'is_published' => true,
            'title' => ['ar' => 'لماذا السعودية'], 'body' => ['ar' => '<p>x</p>'],
            'meta_title' => ['ar' => 'x'], 'meta_description' => ['ar' => 'x'],
        ]);
        PageSection::create([
            'page_id' => $whyInvest->id, 'key' => 'vision-2030', 'sort_order' => 0, 'is_active' => true,
            'content' => ['title' => ['ar' => 'رؤية 2030'], 'description' => ['ar' => 'وصف'], 'icon' => 'chart-line'],
        ]);

        $formation = Page::create([
            'slug' => 'formation-process', 'is_published' => true,
            'title' => ['ar' => 'خطوات التأسيس'], 'body' => ['ar' => '<p>x</p>'],
            'meta_title' => ['ar' => 'x'], 'meta_description' => ['ar' => 'x'],
        ]);
        PageSection::create([
            'page_id' => $formation->id, 'key' => 'step-1', 'sort_order' => 0, 'is_active' => true,
            'content' => ['title' => ['ar' => 'التقديم الأولي'], 'description' => ['ar' => 'وصف'], 'icon' => null],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('لماذا تستثمر في السعودية');
        $response->assertSee('رؤية 2030');
        $response->assertSee('رحلتك نحو تأسيس شركتك');
        $response->assertSee('التقديم الأولي');
    }

    public function test_home_page_shows_testimonials_when_active_testimonials_exist(): void
    {
        Testimonial::create([
            'client_name' => 'أحمد العتيبي', 'quote' => ['ar' => 'خدمة ممتازة'], 'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('ماذا يقول عملاؤنا');
        $response->assertSee('أحمد العتيبي');
        $response->assertSee('خدمة ممتازة');
    }

    public function test_home_page_shows_latest_articles_when_published_articles_exist(): void
    {
        Article::create([
            'slug' => 'guide-1', 'title' => ['ar' => 'دليل التأسيس'],
            'excerpt' => ['ar' => 'ملخص المقال'], 'body' => ['ar' => '<p>محتوى</p>'],
            'is_published' => true, 'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('أحدث المقالات');
        $response->assertSee('دليل التأسيس');
    }

    public function test_navbar_has_services_dropdown_countries_and_blog_label(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('كل الخدمات');
        $response->assertSee('الدول');
        $response->assertSee('المدونة');
    }

    /**
     * WhatsApp was moved out of the navbar entirely and into a
     * site-wide floating button (see resources/views/components/
     * whatsapp-float-button.blade.php) — this proves both halves of
     * that: no WhatsApp link inside the <nav>/mobile-drawer markup, but
     * the floating button (identifiable by its fixed bottom-left
     * classes) still links to the real number.
     */
    public function test_whatsapp_is_not_in_the_navbar_but_is_a_floating_button(): void
    {
        \App\Models\Setting::create(['key' => 'contact_whatsapp', 'value' => '+966500000000', 'group' => 'contact']);

        $response = $this->get('/');
        $html = $response->getContent();

        $response->assertOk();
        $response->assertSee('wa.me/966500000000', false);
        $response->assertSee('fixed bottom-6 left-6', false);

        // Covers both the desktop bar and the mobile drawer, which both
        // live inside <header>...</header>; the floating button is
        // rendered separately, right before </body>.
        $headerEnd = strpos($html, '</header>');
        $headerMarkup = substr($html, 0, $headerEnd);
        $this->assertStringNotContainsString('wa.me/', $headerMarkup);
    }

    public function test_tracking_scripts_render_nothing_when_all_settings_inactive(): void
    {
        TrackingSetting::create(['key' => 'meta_pixel_id', 'value' => '123456789', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('connect.facebook.net', false);
        $response->assertDontSee('fbq(', false);
    }

    public function test_tracking_scripts_render_meta_pixel_base_code_when_active_with_a_test_id(): void
    {
        TrackingSetting::create(['key' => 'meta_pixel_id', 'value' => '999999999999999', 'is_active' => true]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('connect.facebook.net/en_US/fbevents.js', false);
        $response->assertSee("fbq('init', '999999999999999');", false);
        $response->assertSee('facebook.com/tr?id=999999999999999', false);
    }
}
