<?php

namespace Tests\Feature\Dashboard;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class TestimonialControllerTest extends TestCase
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
            'client_name' => 'Ahmed Al-Otaibi',
            'client_title' => 'CEO',
            'client_country' => 'Saudi Arabia',
            'quote' => ['ar' => 'خدمة ممتازة', 'en' => 'Excellent service'],
            'sort_order' => 1,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        Testimonial::create($this->validPayload());

        $response = $this->actingAs($admin)->get(route('dashboard.testimonials.index'));

        $response->assertOk();
        $response->assertSee('Ahmed Al-Otaibi');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.testimonials.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.testimonials.index'))->assertForbidden();
    }

    public function test_store_creates_a_valid_testimonial(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.testimonials.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard.testimonials.index'));
        $this->assertDatabaseHas('testimonials', ['client_name' => 'Ahmed Al-Otaibi']);
    }

    public function test_store_rejects_missing_arabic_quote(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload();
        unset($payload['quote']['ar']);

        $response = $this->actingAs($admin)->post(route('dashboard.testimonials.store'), $payload);

        $response->assertSessionHasErrors('quote.ar');
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_store_with_image_upload_stores_file_and_path(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload(['avatar' => UploadedFile::fake()->image('avatar.jpg')]);

        $this->actingAs($admin)->post(route('dashboard.testimonials.store'), $payload);

        $testimonial = Testimonial::first();
        $this->assertNotNull($testimonial->avatar);
        Storage::disk('public')->assertExists($testimonial->avatar);
    }

    public function test_store_rejects_non_image_file(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload([
            'avatar' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ]);

        $response = $this->actingAs($admin)->post(route('dashboard.testimonials.store'), $payload);

        $response->assertSessionHasErrors('avatar');
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_update_modifies_a_testimonial(): void
    {
        $admin = $this->makeAdmin();
        $testimonial = Testimonial::create($this->validPayload());

        $payload = $this->validPayload(['client_name' => 'Sara Al-Harbi']);
        $response = $this->actingAs($admin)->put(route('dashboard.testimonials.update', $testimonial), $payload);

        $response->assertRedirect(route('dashboard.testimonials.index'));
        $this->assertSame('Sara Al-Harbi', $testimonial->fresh()->client_name);
    }

    public function test_update_rejects_invalid_data(): void
    {
        $admin = $this->makeAdmin();
        $testimonial = Testimonial::create($this->validPayload());

        $payload = $this->validPayload(['client_name' => '']);
        $response = $this->actingAs($admin)->put(route('dashboard.testimonials.update', $testimonial), $payload);

        $response->assertSessionHasErrors('client_name');
    }

    public function test_destroy_removes_a_testimonial(): void
    {
        $admin = $this->makeAdmin();
        $testimonial = Testimonial::create($this->validPayload());

        $response = $this->actingAs($admin)->delete(route('dashboard.testimonials.destroy', $testimonial));

        $response->assertRedirect(route('dashboard.testimonials.index'));
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }
}
