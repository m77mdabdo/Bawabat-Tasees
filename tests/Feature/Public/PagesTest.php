<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(string $slug, array $overrides = []): Page
    {
        return Page::create(array_merge([
            'slug' => $slug,
            'is_published' => true,
            'title' => ['ar' => 'عنوان الصفحة'],
            'body' => ['ar' => '<p>محتوى تعريفي</p>'],
            'meta_title' => ['ar' => 'عنوان ميتا'],
            'meta_description' => ['ar' => 'وصف ميتا'],
        ], $overrides));
    }

    public function test_about_page_returns_200_with_content(): void
    {
        $this->makePage('about', ['title' => ['ar' => 'من نحن']]);

        $response = $this->get(route('pages.about'));

        $response->assertOk();
        $response->assertSee('من نحن');
        $response->assertSee('محتوى تعريفي');
    }

    /**
     * Real Pexels photos/video (licensed for commercial use) were added
     * to the About page — proves the assets are actually referenced, not
     * just present on disk. The video itself is click-to-play, never
     * autoplay, so only the poster image + <source> tag need to be
     * present on page load.
     */
    public function test_about_page_shows_real_photos_and_click_to_play_video(): void
    {
        $this->makePage('about');

        $response = $this->get(route('pages.about'));

        $response->assertOk();
        $response->assertSee('images/about/about-team-meeting.jpg', false);
        $response->assertSee('images/about/about-office-building.jpg', false);
        $response->assertSee('images/about/about-office-tour-poster.jpg', false);
        $response->assertSee('videos/about/about-office-tour.mp4', false);

        // The <video> element itself is present in the markup (so the
        // click handler has something to reveal) but starts hidden —
        // it is never autoplaying/visible on initial page load.
        $response->assertSee('x-show="playing"', false);
        $response->assertSee('style="display: none;"', false);
    }

    public function test_about_page_404s_when_page_missing(): void
    {
        $this->get(route('pages.about'))->assertNotFound();
    }

    public function test_about_page_404s_when_unpublished(): void
    {
        $this->makePage('about', ['is_published' => false]);

        $this->get(route('pages.about'))->assertNotFound();
    }

    public function test_why_invest_page_shows_active_sections_only(): void
    {
        $page = $this->makePage('why-invest-saudi-arabia', ['title' => ['ar' => 'لماذا تستثمر']]);
        $page->sections()->create([
            'key' => 'active-one',
            'sort_order' => 1,
            'is_active' => true,
            'content' => ['title' => ['ar' => 'ميزة نشطة'], 'description' => ['ar' => 'وصف'], 'icon' => 'gift'],
        ]);
        $page->sections()->create([
            'key' => 'inactive-one',
            'sort_order' => 2,
            'is_active' => false,
            'content' => ['title' => ['ar' => 'ميزة غير نشطة'], 'description' => null, 'icon' => null],
        ]);

        $response = $this->get(route('pages.why-invest'));

        $response->assertOk();
        $response->assertSee('ميزة نشطة');
        $response->assertDontSee('ميزة غير نشطة');
    }

    public function test_formation_process_page_shows_steps_in_sort_order(): void
    {
        $page = $this->makePage('formation-process', ['title' => ['ar' => 'خطوات التأسيس']]);
        $page->sections()->create(['key' => 'step-2', 'sort_order' => 2, 'is_active' => true, 'content' => ['title' => ['ar' => 'الخطوة الثانية'], 'description' => null, 'icon' => null]]);
        $page->sections()->create(['key' => 'step-1', 'sort_order' => 1, 'is_active' => true, 'content' => ['title' => ['ar' => 'الخطوة الأولى'], 'description' => null, 'icon' => null]]);

        $content = $this->get(route('pages.formation-process'))->getContent();

        $this->assertTrue(strpos($content, 'الخطوة الأولى') < strpos($content, 'الخطوة الثانية'));
    }

    public function test_requirements_page_returns_200_with_sections(): void
    {
        $page = $this->makePage('required-documents', ['title' => ['ar' => 'المستندات المطلوبة']]);
        $page->sections()->create([
            'key' => 'passport',
            'sort_order' => 1,
            'is_active' => true,
            'content' => ['title' => ['ar' => 'نسخة من جواز السفر'], 'description' => null, 'icon' => 'identification'],
        ]);

        $response = $this->get(route('pages.requirements'));

        $response->assertOk();
        $response->assertSee('المستندات المطلوبة');
        $response->assertSee('نسخة من جواز السفر');
    }

    public function test_dashboard_edit_is_reflected_on_public_page(): void
    {
        $page = $this->makePage('about', ['title' => ['ar' => 'من نحن']]);

        $page->update(['title' => ['ar' => 'عنوان جديد بعد التعديل']]);

        $response = $this->get(route('pages.about'));

        $response->assertSee('عنوان جديد بعد التعديل');
    }
}
