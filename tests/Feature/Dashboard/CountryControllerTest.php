<?php

namespace Tests\Feature\Dashboard;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class CountryControllerTest extends TestCase
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
            'slug' => 'saudi-arabia',
            'name' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
            'notes' => ['ar' => 'ملاحظات', 'en' => 'Notes'],
            'sort_order' => 1,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        Country::create($this->validPayload());

        $response = $this->actingAs($admin)->get(route('dashboard.countries.index'));

        $response->assertOk();
        $response->assertSee('السعودية');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.countries.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.countries.index'))->assertForbidden();
    }

    public function test_store_creates_a_valid_country(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.countries.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard.countries.index'));
        $this->assertDatabaseHas('countries', ['slug' => 'saudi-arabia']);
    }

    public function test_store_rejects_missing_arabic_name(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload();
        unset($payload['name']['ar']);

        $response = $this->actingAs($admin)->post(route('dashboard.countries.store'), $payload);

        $response->assertSessionHasErrors('name.ar');
        $this->assertDatabaseCount('countries', 0);
    }

    public function test_store_with_image_upload_stores_file_and_path(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload(['flag_image' => UploadedFile::fake()->image('flag.jpg')]);

        $this->actingAs($admin)->post(route('dashboard.countries.store'), $payload);

        $country = Country::first();
        $this->assertNotNull($country->flag_image);
        Storage::disk('public')->assertExists($country->flag_image);
    }

    public function test_store_rejects_non_image_file(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload([
            'flag_image' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ]);

        $response = $this->actingAs($admin)->post(route('dashboard.countries.store'), $payload);

        $response->assertSessionHasErrors('flag_image');
        $this->assertDatabaseCount('countries', 0);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $admin = $this->makeAdmin();
        Country::create($this->validPayload());

        $response = $this->actingAs($admin)->post(route('dashboard.countries.store'), $this->validPayload([
            'name' => ['ar' => 'دولة أخرى', 'en' => 'Another Country'],
        ]));

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('countries', 1);
    }

    public function test_update_modifies_a_country(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create($this->validPayload());

        $payload = $this->validPayload(['name' => ['ar' => 'اسم جديد', 'en' => 'New Name']]);
        $response = $this->actingAs($admin)->put(route('dashboard.countries.update', $country), $payload);

        $response->assertRedirect(route('dashboard.countries.index'));
        $this->assertSame('اسم جديد', $country->fresh()->getTranslation('name', 'ar'));
    }

    public function test_update_rejects_invalid_data(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create($this->validPayload());

        $payload = $this->validPayload();
        unset($payload['name']['ar']);

        $response = $this->actingAs($admin)->put(route('dashboard.countries.update', $country), $payload);

        $response->assertSessionHasErrors('name.ar');
    }

    public function test_destroy_removes_a_country(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create($this->validPayload());

        $response = $this->actingAs($admin)->delete(route('dashboard.countries.destroy', $country));

        $response->assertRedirect(route('dashboard.countries.index'));
        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }
}
