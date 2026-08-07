<?php

namespace Tests\Feature\Console;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslateContentToEnglishTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'about',
            'is_published' => true,
            'title' => ['ar' => 'x'],
            'body' => ['ar' => 'x'],
            'meta_title' => ['ar' => 'x'],
            'meta_description' => ['ar' => 'x'],
        ], $overrides));
    }

    public function test_translates_known_arabic_page_content_to_english(): void
    {
        $this->makePage([
            'title' => ['ar' => 'من نحن'],
            'body' => ['ar' => '<p>بوابة تأسيس الشركات شريكك الموثوق لتأسيس الأعمال والاستثمار في المملكة العربية السعودية. نرافق المستثمرين الأجانب والمحليين في كل خطوة من رحلة التأسيس، من الاستشارة الأولى وحتى استخراج السجل التجاري وانطلاق النشاط التجاري.</p><p>يجمع فريقنا بين المعرفة العميقة بالأنظمة واللوائح المحلية وخبرة عملية في التعامل مع الجهات الحكومية المختصة، لنقدم لعملائنا مساراً واضحاً وسلساً نحو تأسيس شركاتهم بثقة.</p>'],
            'meta_title' => ['ar' => 'من نحن — بوابة تأسيس الشركات'],
            'meta_description' => ['ar' => 'تعرف على بوابة تأسيس الشركات، شريكك الموثوق لتأسيس الأعمال والاستثمار في المملكة العربية السعودية.'],
        ]);

        $this->artisan('content:translate-to-english')->assertSuccessful();

        $page = Page::where('slug', 'about')->first();

        $this->assertSame('About Us', $page->getTranslation('title', 'en'));
        $this->assertStringContainsString('Bawabat Taasees Al Sharikat', $page->getTranslation('body', 'en'));
        $this->assertSame('About Us — Bawabat Taasees Al Sharikat', $page->getTranslation('meta_title', 'en'));

        // Arabic untouched.
        $this->assertSame('من نحن', $page->getTranslation('title', 'ar'));
    }

    public function test_translates_page_section_title_and_description(): void
    {
        $page = $this->makePage(['slug' => 'why-invest-saudi-arabia']);

        PageSection::create([
            'page_id' => $page->id,
            'key' => 'vision-2030',
            'sort_order' => 0,
            'is_active' => true,
            'content' => [
                'title' => ['ar' => 'التوافق مع رؤية 2030'],
                'description' => ['ar' => 'استراتيجية وطنية شاملة تعمل على تنويع الاقتصاد وفتح قطاعات جديدة أمام الاستثمار المحلي والأجنبي.'],
                'icon' => 'chart-line',
            ],
        ]);

        $this->artisan('content:translate-to-english')->assertSuccessful();

        $section = PageSection::where('key', 'vision-2030')->first();

        $this->assertSame('Aligned with Vision 2030', $section->content['title']['en']);
        $this->assertSame(
            'A comprehensive national strategy diversifying the economy and opening new sectors to local and foreign investment.',
            $section->content['description']['en']
        );
    }

    public function test_running_twice_is_idempotent(): void
    {
        $this->makePage(['title' => ['ar' => 'من نحن']]);

        $this->artisan('content:translate-to-english')
            ->expectsOutputToContain('Total fields translated: 1')
            ->assertSuccessful();

        $this->artisan('content:translate-to-english')
            ->expectsOutputToContain('Total fields translated: 0')
            ->expectsOutputToContain('Total fields already had English content (skipped): 1')
            ->assertSuccessful();
    }

    public function test_does_not_overwrite_existing_english_content_without_force(): void
    {
        $this->makePage(['title' => ['ar' => 'من نحن', 'en' => 'A hand-edited title']]);

        $this->artisan('content:translate-to-english')->assertSuccessful();

        $page = Page::where('slug', 'about')->first();

        $this->assertSame('A hand-edited title', $page->getTranslation('title', 'en'));
    }

    public function test_force_flag_overwrites_existing_english_content(): void
    {
        $this->makePage(['title' => ['ar' => 'من نحن', 'en' => 'A hand-edited title']]);

        $this->artisan('content:translate-to-english', ['--force' => true])->assertSuccessful();

        $page = Page::where('slug', 'about')->first();

        $this->assertSame('About Us', $page->getTranslation('title', 'en'));
    }

    /**
     * Content the command's dictionary genuinely has no translation for
     * must be reported, not silently skipped or guessed at — this is the
     * behavior that keeps future (currently nonexistent) Service/Country/
     * Faq/Article/Testimonial content from disappearing into a false
     * "0 translated, 0 skipped, nothing to see here" report.
     */
    public function test_reports_arabic_content_with_no_available_translation(): void
    {
        Service::create([
            'slug' => 'unrecognized-service',
            'name' => ['ar' => 'نص عربي غير موجود في القاموس'],
            'summary' => ['ar' => 'x'],
            'body' => ['ar' => 'x'],
            'requirements' => ['ar' => 'x'],
            'process' => ['ar' => 'x'],
            'is_active' => true,
        ]);

        $this->artisan('content:translate-to-english')
            ->expectsOutputToContain('no translation available')
            ->assertSuccessful();
    }
}
