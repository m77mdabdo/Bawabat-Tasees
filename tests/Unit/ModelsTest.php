<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\TrackingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_is_created_with_translations_and_scopes(): void
    {
        $service = Service::create([
            'slug' => 'company-formation',
            'is_active' => true,
            'name' => ['en' => 'Company Formation', 'ar' => 'تأسيس الشركات'],
            'summary' => ['en' => 'summary', 'ar' => 'ملخص'],
            'body' => ['en' => 'body', 'ar' => 'محتوى'],
            'requirements' => ['en' => 'reqs', 'ar' => 'متطلبات'],
            'process' => ['en' => 'process', 'ar' => 'عملية'],
        ]);

        $this->assertDatabaseHas('services', ['slug' => 'company-formation']);
        $this->assertSame('تأسيس الشركات', $service->getTranslation('name', 'ar'));
        $this->assertSame('Company Formation', $service->getTranslation('name', 'en'));
        $this->assertTrue(Service::active()->whereKey($service->id)->exists());
    }

    public function test_page_generates_slug_and_has_sections_and_seo_meta(): void
    {
        $page = Page::create([
            'title' => ['en' => 'Home', 'ar' => 'الرئيسية'],
            'body' => ['en' => 'welcome', 'ar' => 'مرحبا'],
            'meta_title' => ['en' => 'meta', 'ar' => 'ميتا'],
            'meta_description' => ['en' => 'meta desc', 'ar' => 'وصف ميتا'],
        ]);

        $this->assertSame('home', $page->slug);

        PageSection::create([
            'page_id' => $page->id,
            'key' => 'hero',
            'content' => ['title' => 'Hero'],
            'is_active' => true,
        ]);

        $this->assertCount(1, $page->sections);

        SeoMeta::create([
            'seo_metable_type' => Page::class,
            'seo_metable_id' => $page->id,
            'meta_title' => ['en' => 'SEO title'],
        ]);

        $this->assertSame('SEO title', $page->seoMeta->getTranslation('meta_title', 'en'));
    }

    public function test_lead_belongs_to_requested_service_and_casts_touch_data(): void
    {
        $service = Service::create([
            'slug' => 'visa-services',
            'name' => ['en' => 'Visa Services'],
            'summary' => ['en' => 'summary'],
            'body' => ['en' => 'body'],
            'requirements' => ['en' => 'reqs'],
            'process' => ['en' => 'process'],
        ]);

        $lead = Lead::create([
            'full_name' => 'Ahmed Al-Otaibi',
            'phone' => '+966501234567',
            'requested_service_id' => $service->id,
            'type' => 'consultation',
            'first_touch' => ['utm_source' => 'google'],
        ]);

        $this->assertTrue($lead->requestedService->is($service));
        $this->assertIsArray($lead->first_touch);
        $this->assertSame('google', $lead->first_touch['utm_source']);
    }

    public function test_setting_stores_json_value(): void
    {
        $setting = Setting::create([
            'key' => 'contact_phone',
            'value' => ['phone' => '+966500000000'],
            'group' => 'contact',
        ]);

        $this->assertIsArray($setting->value);
        $this->assertSame('+966500000000', $setting->value['phone']);
    }

    public function test_country_faq_testimonial_article_are_created(): void
    {
        $country = Country::create([
            'name' => ['en' => 'Saudi Arabia', 'ar' => 'السعودية'],
        ]);
        $this->assertSame('saudi-arabia', $country->slug);

        $faq = Faq::create([
            'question' => ['en' => 'How?'],
            'answer' => ['en' => 'Like this'],
            'is_active' => true,
        ]);
        $this->assertTrue(Faq::active()->whereKey($faq->id)->exists());

        $testimonial = Testimonial::create([
            'client_name' => 'Sara',
            'quote' => ['en' => 'Great service'],
        ]);
        $this->assertSame('Great service', $testimonial->getTranslation('quote', 'en'));

        $article = Article::create([
            'title' => ['en' => 'News'],
            'body' => ['en' => 'body'],
        ]);
        $this->assertSame('news', $article->slug);
    }

    public function test_media_belongs_to_uploader(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        $media = Media::create([
            'path' => 'uploads/x.png',
            'mime_type' => 'image/png',
            'size' => 12345,
            'type' => 'image',
            'uploaded_by' => $user->id,
        ]);

        $this->assertTrue($media->uploadedBy->is($user));
    }

    public function test_conversion_event_belongs_to_lead(): void
    {
        $lead = Lead::create([
            'full_name' => 'Test Lead',
            'phone' => '0500000000',
        ]);

        $conversion = ConversionEvent::create([
            'event_type' => 'Lead',
            'lead_id' => $lead->id,
            'occurred_at' => now(),
            'utm_snapshot' => ['utm_source' => 'google'],
        ]);

        $this->assertTrue($conversion->lead->is($lead));
        $this->assertIsArray($conversion->utm_snapshot);
    }

    public function test_lead_source_campaign_and_tracking_setting_are_created(): void
    {
        $source = LeadSource::create(['key' => 'meta_ads', 'label' => 'Meta Ads', 'is_active' => true]);
        $this->assertTrue(LeadSource::active()->whereKey($source->id)->exists());

        $campaign = Campaign::create(['name' => 'Ramadan Promo', 'is_active' => true]);
        $this->assertTrue(Campaign::active()->whereKey($campaign->id)->exists());

        $tracking = TrackingSetting::create(['key' => 'meta_pixel_id', 'value' => '123', 'is_active' => true]);
        $this->assertTrue(TrackingSetting::active()->whereKey($tracking->id)->exists());
    }

    public function test_user_is_admin_is_cast_but_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);

        $this->assertFalse($user->fresh()->is_admin);
        $this->assertNotContains('is_admin', $user->getFillable());

        $user->is_admin = true;
        $user->save();

        $this->assertTrue($user->fresh()->is_admin);
        $this->assertIsBool($user->fresh()->is_admin);
    }
}
