<?php

namespace Tests\Feature\Dashboard;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function makePage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'about',
            'is_published' => true,
            'title' => ['ar' => 'من نحن'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'meta_title' => ['ar' => 'عنوان ميتا'],
            'meta_description' => ['ar' => 'وصف ميتا'],
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => ['ar' => 'من نحن'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'meta_title' => ['ar' => 'عنوان ميتا'],
            'meta_description' => ['ar' => 'وصف ميتا'],
            'is_published' => '1',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $this->makePage();

        $response = $this->actingAs($admin)->get(route('dashboard.pages.index'));

        $response->assertOk();
        $response->assertSee('من نحن');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.pages.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.pages.index'))->assertForbidden();
    }

    public function test_update_modifies_a_page(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();

        $payload = $this->validPayload(['title' => ['ar' => 'من نحن — محدث']]);
        $response = $this->actingAs($admin)->put(route('dashboard.pages.update', $page), $payload);

        $response->assertRedirect(route('dashboard.pages.edit', $page));
        $this->assertSame('من نحن — محدث', $page->fresh()->getTranslation('title', 'ar'));
    }

    public function test_update_rejects_missing_arabic_title(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();

        $payload = $this->validPayload();
        unset($payload['title']['ar']);

        $response = $this->actingAs($admin)->put(route('dashboard.pages.update', $page), $payload);

        $response->assertSessionHasErrors('title.ar');
    }

    public function test_update_sanitizes_body_html(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();

        $payload = $this->validPayload([
            'body' => ['ar' => "<p>Hello</p><script>alert('xss')</script>"],
        ]);
        $this->actingAs($admin)->put(route('dashboard.pages.update', $page), $payload);

        $stored = $page->fresh()->getTranslation('body', 'ar');
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringContainsString('<p>Hello</p>', $stored);
    }

    public function test_no_create_or_destroy_routes_exist_for_pages(): void
    {
        $this->assertFalse(Route::has('dashboard.pages.store'));
        $this->assertFalse(Route::has('dashboard.pages.destroy'));
        $this->assertFalse(Route::has('dashboard.pages.create'));
    }
}
