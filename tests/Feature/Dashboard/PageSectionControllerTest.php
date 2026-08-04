<?php

namespace Tests\Feature\Dashboard;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class PageSectionControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function makePage(): Page
    {
        return Page::create([
            'slug' => 'why-invest-saudi-arabia',
            'is_published' => true,
            'title' => ['ar' => 'لماذا تستثمر'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'meta_title' => ['ar' => 'عنوان'],
            'meta_description' => ['ar' => 'وصف'],
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'vision-2030',
            'title' => ['ar' => 'التوافق مع رؤية 2030'],
            'description' => ['ar' => 'وصف الميزة'],
            'icon' => 'chart-line',
            'sort_order' => 1,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();
        $page->sections()->create([
            'key' => 'vision-2030',
            'sort_order' => 0,
            'is_active' => true,
            'content' => ['title' => ['ar' => 'التوافق مع رؤية 2030'], 'description' => null, 'icon' => null],
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.pages.sections.index', $page));

        $response->assertOk();
        $response->assertSee('التوافق مع رؤية 2030');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $page = $this->makePage();

        $this->get(route('dashboard.pages.sections.index', $page))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();
        $page = $this->makePage();

        $this->actingAs($user)->get(route('dashboard.pages.sections.index', $page))->assertForbidden();
    }

    public function test_store_creates_a_section_with_correct_content_shape(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();

        $response = $this->actingAs($admin)->post(route('dashboard.pages.sections.store', $page), $this->validPayload());

        $response->assertRedirect(route('dashboard.pages.sections.index', $page));
        $section = $page->sections()->first();
        $this->assertSame('vision-2030', $section->key);
        $this->assertSame('التوافق مع رؤية 2030', $section->title);
        $this->assertSame('وصف الميزة', $section->description);
        $this->assertSame('chart-line', $section->icon);
    }

    public function test_store_rejects_missing_arabic_title(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();

        $payload = $this->validPayload();
        unset($payload['title']['ar']);

        $response = $this->actingAs($admin)->post(route('dashboard.pages.sections.store', $page), $payload);

        $response->assertSessionHasErrors('title.ar');
        $this->assertSame(0, $page->sections()->count());
    }

    public function test_store_rejects_missing_key(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();

        $payload = $this->validPayload(['key' => '']);

        $response = $this->actingAs($admin)->post(route('dashboard.pages.sections.store', $page), $payload);

        $response->assertSessionHasErrors('key');
    }

    public function test_index_respects_sort_order(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();
        $page->sections()->create(['key' => 'second', 'sort_order' => 2, 'is_active' => true, 'content' => ['title' => ['ar' => 'القسم الثاني'], 'description' => null, 'icon' => null]]);
        $page->sections()->create(['key' => 'first', 'sort_order' => 1, 'is_active' => true, 'content' => ['title' => ['ar' => 'القسم الأول'], 'description' => null, 'icon' => null]]);

        $response = $this->actingAs($admin)->get(route('dashboard.pages.sections.index', $page));

        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'القسم الأول') < strpos($content, 'القسم الثاني'));
    }

    public function test_update_modifies_a_section(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();
        $section = $page->sections()->create([
            'key' => 'vision-2030',
            'sort_order' => 0,
            'is_active' => true,
            'content' => ['title' => ['ar' => 'قديم'], 'description' => null, 'icon' => null],
        ]);

        $payload = $this->validPayload(['title' => ['ar' => 'جديد']]);
        $response = $this->actingAs($admin)->put(route('dashboard.pages.sections.update', [$page, $section]), $payload);

        $response->assertRedirect(route('dashboard.pages.sections.index', $page));
        $this->assertSame('جديد', $section->fresh()->title);
    }

    public function test_destroy_removes_a_section(): void
    {
        $admin = $this->makeAdmin();
        $page = $this->makePage();
        $section = $page->sections()->create([
            'key' => 'vision-2030',
            'sort_order' => 0,
            'is_active' => true,
            'content' => ['title' => ['ar' => 'قسم'], 'description' => null, 'icon' => null],
        ]);

        $response = $this->actingAs($admin)->delete(route('dashboard.pages.sections.destroy', [$page, $section]));

        $response->assertRedirect(route('dashboard.pages.sections.index', $page));
        $this->assertDatabaseMissing('page_sections', ['id' => $section->id]);
    }
}
