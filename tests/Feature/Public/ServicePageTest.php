<?php

namespace Tests\Feature\Public;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePageTest extends TestCase
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
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_index_shows_active_services_only(): void
    {
        $this->makeService();
        $this->makeService(['slug' => 'inactive-service', 'name' => ['ar' => 'خدمة غير نشطة'], 'is_active' => false]);

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('تأسيس الشركات');
        $response->assertDontSee('خدمة غير نشطة');
    }

    public function test_index_shows_flagship_badge(): void
    {
        $this->makeService(['is_flagship' => true]);

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('خدمة رئيسية');
    }

    public function test_index_shows_empty_state_when_no_services(): void
    {
        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('لا توجد خدمات متاحة حالياً');
    }

    public function test_show_returns_200_for_active_service_with_all_fields(): void
    {
        $service = $this->makeService();

        $response = $this->get(route('services.show', $service));

        $response->assertOk();
        $response->assertSee('تأسيس الشركات');
        $response->assertSee('ملخص الخدمة');
        $response->assertSee('محتوى الخدمة');
        $response->assertSee('المتطلبات');
        $response->assertSee('خطوات العملية');
    }

    public function test_show_returns_404_for_inactive_service(): void
    {
        $service = $this->makeService(['slug' => 'inactive-service', 'is_active' => false]);

        $this->get(route('services.show', $service))->assertNotFound();
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/services/does-not-exist')->assertNotFound();
    }

    public function test_dashboard_edit_is_reflected_on_public_page(): void
    {
        $service = $this->makeService();

        $service->update(['name' => ['ar' => 'اسم جديد بعد التعديل']]);

        $response = $this->get(route('services.show', $service));

        $response->assertSee('اسم جديد بعد التعديل');
    }
}
