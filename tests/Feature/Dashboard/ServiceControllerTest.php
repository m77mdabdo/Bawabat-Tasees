<?php

namespace Tests\Feature\Dashboard;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات', 'en' => 'Company Formation'],
            'summary' => ['ar' => 'ملخص الخدمة', 'en' => 'Service summary'],
            'body' => ['ar' => 'محتوى الخدمة', 'en' => 'Service body'],
            'requirements' => ['ar' => 'المتطلبات', 'en' => 'Requirements'],
            'process' => ['ar' => 'خطوات العملية', 'en' => 'Process steps'],
            'sort_order' => 1,
            'is_active' => '1',
            'is_flagship' => '0',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        Service::create($this->validPayload());

        $response = $this->actingAs($admin)->get(route('dashboard.services.index'));

        $response->assertOk();
        $response->assertSee('تأسيس الشركات');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.services.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.services.index'))->assertForbidden();
    }

    public function test_store_creates_a_valid_service(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.services.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard.services.index'));
        $this->assertDatabaseHas('services', ['slug' => 'company-formation']);
    }

    public function test_store_auto_generates_slug_from_arabic_name_when_omitted(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload(['slug' => null]);

        $this->actingAs($admin)->post(route('dashboard.services.store'), $payload);

        $service = Service::first();
        $this->assertNotEmpty($service->slug);
    }

    public function test_store_rejects_missing_arabic_name(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload();
        unset($payload['name']['ar']);

        $response = $this->actingAs($admin)->post(route('dashboard.services.store'), $payload);

        $response->assertSessionHasErrors('name.ar');
        $this->assertDatabaseCount('services', 0);
    }

    public function test_store_with_image_upload_stores_file_and_path(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload(['cover_image' => UploadedFile::fake()->image('cover.jpg')]);

        $this->actingAs($admin)->post(route('dashboard.services.store'), $payload);

        $service = Service::first();
        $this->assertNotNull($service->cover_image);
        Storage::disk('public')->assertExists($service->cover_image);
    }

    public function test_store_rejects_non_image_file(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload([
            'cover_image' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ]);

        $response = $this->actingAs($admin)->post(route('dashboard.services.store'), $payload);

        $response->assertSessionHasErrors('cover_image');
        $this->assertDatabaseCount('services', 0);
    }

    public function test_update_modifies_a_service(): void
    {
        $admin = $this->makeAdmin();
        $service = Service::create($this->validPayload());

        $payload = $this->validPayload(['name' => ['ar' => 'اسم جديد', 'en' => 'New Name']]);
        $response = $this->actingAs($admin)->put(route('dashboard.services.update', $service), $payload);

        $response->assertRedirect(route('dashboard.services.index'));
        $this->assertSame('اسم جديد', $service->fresh()->getTranslation('name', 'ar'));
    }

    public function test_update_rejects_invalid_data(): void
    {
        $admin = $this->makeAdmin();
        $service = Service::create($this->validPayload());

        $payload = $this->validPayload();
        unset($payload['name']['ar']);

        $response = $this->actingAs($admin)->put(route('dashboard.services.update', $service), $payload);

        $response->assertSessionHasErrors('name.ar');
    }

    public function test_destroy_soft_deletes_a_service(): void
    {
        $admin = $this->makeAdmin();
        $service = Service::create($this->validPayload());

        $response = $this->actingAs($admin)->delete(route('dashboard.services.destroy', $service));

        $response->assertRedirect(route('dashboard.services.index'));
        $this->assertSoftDeleted($service);
    }
}
