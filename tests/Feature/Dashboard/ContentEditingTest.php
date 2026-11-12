<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role;
use App\Filament\Pages\TrackingSettings;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Article;
use App\Models\Lead;
use App\Models\MenuItem;
use App\Models\Service;
use App\Models\TrackingSetting;
use Database\Seeders\TrackingSettingSeeder;
use Livewire\Livewire;

/** Content edits keep both languages, stay safe to render, and go through the domain services. */
class ContentEditingTest extends DashboardTestCase
{
    private function serviceData(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => ['ar' => 'تأسيس شركة', 'en' => 'Company formation'],
            'summary' => ['ar' => 'ملخص', 'en' => ''],
            'body' => ['ar' => 'وصف', 'en' => ''],
            'requirements' => ['ar' => 'متطلبات', 'en' => ''],
            'process' => ['ar' => 'خطوات', 'en' => ''],
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides);
    }

    public function test_a_service_is_saved_in_both_languages_and_blank_english_falls_back(): void
    {
        $this->actingAs($this->makeStaff(Role::Editor));

        Livewire::test(CreateService::class)->fillForm($this->serviceData())->call('create')->assertHasNoFormErrors();

        $service = Service::firstOrFail();
        $this->assertSame('Company formation', $service->getTranslation('name', 'en'));
        $this->assertNotEmpty($service->slug);
        // Blank English is not stored, so the public site falls back to Arabic instead of showing nothing.
        $this->assertSame(['ar' => 'ملخص'], $service->getTranslations('summary'));
    }

    public function test_arabic_is_required(): void
    {
        $this->actingAs($this->makeStaff(Role::Editor));

        Livewire::test(CreateService::class)->fillForm($this->serviceData(['name' => ['ar' => '']]))->call('create')
            ->assertHasFormErrors(['name.ar' => 'required']);
    }

    public function test_the_edit_form_loads_every_language(): void
    {
        $this->actingAs($this->makeStaff(Role::Editor));
        $service = Service::create($this->serviceData());

        Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->assertFormSet(['name.ar' => 'تأسيس شركة', 'name.en' => 'Company formation']);
    }

    public function test_article_html_is_purified_whatever_writes_it(): void
    {
        $this->actingAs($this->makeStaff(Role::Editor));

        Livewire::test(CreateArticle::class)->fillForm([
            'title' => ['ar' => '<b>عنوان</b>'],
            'body' => ['ar' => '<p>نص</p><script>alert(1)</script><img src=x onerror=alert(1)>'],
            'is_published' => true,
        ])->call('create')->assertHasNoFormErrors();

        $article = Article::firstOrFail();
        $this->assertSame('عنوان', $article->getTranslation('title', 'ar'));
        $this->assertStringNotContainsString('<script', $article->getTranslation('body', 'ar'));
        $this->assertStringNotContainsString('onerror', $article->getTranslation('body', 'ar'));
        $this->assertStringContainsString('<p>نص</p>', $article->getTranslation('body', 'ar'));
    }

    public function test_a_service_description_renders_as_safe_html_on_the_site(): void
    {
        $service = Service::create($this->serviceData([
            'body' => ['ar' => '<p>فقرة أولى</p><script>alert(1)</script>'],
            'requirements' => ['ar' => "سطر<b>١</b>\nسطر ٢"],
        ]));

        $html = $this->get(route('services.show', $service))->assertOk()->getContent();

        $this->assertStringContainsString('<p>فقرة أولى</p>', $html);
        $this->assertStringNotContainsString('&lt;p&gt;', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringContainsString('سطر١<br />', $html);
    }

    public function test_a_menu_item_goes_through_the_menu_service(): void
    {
        $this->actingAs($this->makeStaff(Role::Editor));
        MenuItem::create(['label' => ['ar' => 'أول'], 'link_type' => 'route', 'route_name' => 'home', 'sort_order' => 5]);

        Livewire::test(CreateMenuItem::class)->fillForm([
            'label' => ['ar' => 'خدماتنا', 'en' => ''],
            'link_type' => 'route', 'route_name' => 'services.index', 'is_visible' => true,
        ])->call('create')->assertHasNoFormErrors();

        $item = MenuItem::where('route_name', 'services.index')->firstOrFail();
        $this->assertSame(6, $item->sort_order); // placed at the end of its level
        $this->assertSame(['ar' => 'خدماتنا'], $item->getTranslations('label'));
    }

    public function test_a_new_campaign_links_the_leads_that_carry_its_id(): void
    {
        $this->actingAs($this->makeStaff(Role::Manager));
        $lead = Lead::factory()->create(['campaign_id' => 'G-123', 'linked_campaign_id' => null]);

        Livewire::test(CreateCampaign::class)->fillForm(['name' => 'Spring', 'platform' => 'google', 'external_campaign_id' => 'G-123', 'currency' => 'SAR', 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();

        $this->assertNotNull($lead->fresh()->linked_campaign_id);
    }

    public function test_tracking_ids_must_match_their_format_and_need_a_value_to_switch_on(): void
    {
        $this->seed(TrackingSettingSeeder::class);
        $this->actingAs($this->makeAdmin());

        Livewire::test(TrackingSettings::class)
            ->fillForm(['ga4_measurement_id' => ['value' => 'not-an-id', 'is_active' => true], 'meta_pixel_id' => ['value' => '', 'is_active' => true]])
            ->call('save')
            ->assertHasFormErrors(['ga4_measurement_id.value', 'meta_pixel_id.value']);

        Livewire::test(TrackingSettings::class)
            ->fillForm(['ga4_measurement_id' => ['value' => 'G-ABC1234', 'is_active' => true]])
            ->call('save')->assertHasNoFormErrors();

        $this->assertTrue(TrackingSetting::where('key', 'ga4_measurement_id')->value('is_active'));
    }
}
