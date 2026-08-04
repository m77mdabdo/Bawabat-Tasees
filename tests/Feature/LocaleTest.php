<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_locale_persists_in_session_and_applies_to_the_next_request(): void
    {
        $this->assertSame('ar', app()->getLocale());

        $this->get(route('locale.switch', 'en'))->assertRedirect();
        $this->assertSame('en', session('locale'));

        // A fresh request (the redirect target) picks the session value
        // back up via App\Http\Middleware\SetLocale.
        $this->get('/');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_switching_back_to_arabic_works_too(): void
    {
        $this->withSession(['locale' => 'en'])->get(route('locale.switch', 'ar'));

        $this->assertSame('ar', session('locale'));
    }

    public function test_switching_locale_redirects_back_to_the_previous_page(): void
    {
        $response = $this->from(route('services.index'))->get(route('locale.switch', 'en'));

        $response->assertRedirect(route('services.index'));
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->get(route('locale.switch', 'fr'))->assertNotFound();
        $this->assertNull(session('locale'));
    }

    public function test_html_dir_is_rtl_for_arabic_and_ltr_for_english(): void
    {
        $this->get('/')->assertSee('dir="rtl"', false);

        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertSee('dir="ltr"', false);
    }

    /**
     * The whole point of confirming the translatable fallback locale is
     * 'ar': a record with no English translation yet must still show its
     * Arabic value when the site is switched to English, not a blank
     * string. Proven both at the model level and over a real HTTP
     * request through the full middleware/session stack.
     */
    public function test_translatable_fallback_returns_arabic_when_english_is_missing(): void
    {
        $service = Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'ملخص الخدمة'],
            'body' => ['ar' => 'محتوى الخدمة'],
            'requirements' => ['ar' => 'المتطلبات'],
            'process' => ['ar' => 'خطوات العملية'],
            'is_active' => true,
        ]);

        app()->setLocale('en');

        $this->assertSame('تأسيس الشركات', $service->fresh()->name);
    }

    public function test_service_page_shows_arabic_name_over_http_when_locale_is_english_and_no_translation_exists(): void
    {
        Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'ملخص الخدمة'],
            'body' => ['ar' => 'محتوى الخدمة'],
            'requirements' => ['ar' => 'المتطلبات'],
            'process' => ['ar' => 'خطوات العملية'],
            'is_active' => true,
        ]);

        $response = $this->withSession(['locale' => 'en'])->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('تأسيس الشركات');
    }
}
