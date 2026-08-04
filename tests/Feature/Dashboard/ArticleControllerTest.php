<?php

namespace Tests\Feature\Dashboard;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
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
            'slug' => 'welcome-to-bawabat',
            'title' => ['ar' => 'مرحبا بكم', 'en' => 'Welcome'],
            'excerpt' => ['ar' => 'مقتطف قصير', 'en' => 'Short excerpt'],
            'body' => ['ar' => '<p>محتوى المقالة</p>', 'en' => '<p>Article body</p>'],
            'is_published' => '1',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        Article::create($this->validPayload());

        $response = $this->actingAs($admin)->get(route('dashboard.articles.index'));

        $response->assertOk();
        $response->assertSee('مرحبا بكم');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.articles.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.articles.index'))->assertForbidden();
    }

    public function test_store_creates_a_valid_article(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard.articles.index'));
        $this->assertDatabaseHas('articles', ['slug' => 'welcome-to-bawabat']);
    }

    public function test_store_rejects_missing_arabic_title(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload();
        unset($payload['title']['ar']);

        $response = $this->actingAs($admin)->post(route('dashboard.articles.store'), $payload);

        $response->assertSessionHasErrors('title.ar');
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $admin = $this->makeAdmin();
        Article::create($this->validPayload());

        $response = $this->actingAs($admin)->post(route('dashboard.articles.store'), $this->validPayload([
            'title' => ['ar' => 'عنوان آخر', 'en' => 'Another title'],
        ]));

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_store_with_cover_image_upload_stores_file_and_path(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload(['cover_image' => UploadedFile::fake()->image('cover.jpg')]);

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $payload);

        $article = Article::first();
        $this->assertNotNull($article->cover_image);
        Storage::disk('public')->assertExists($article->cover_image);
    }

    public function test_store_rejects_non_image_cover(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload([
            'cover_image' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ]);

        $response = $this->actingAs($admin)->post(route('dashboard.articles.store'), $payload);

        $response->assertSessionHasErrors('cover_image');
        $this->assertDatabaseCount('articles', 0);
    }

    /**
     * The critical security test for this task: a script tag and an
     * onerror event-handler attribute must both be stripped from the body
     * before it is ever persisted, while safe markup survives untouched.
     */
    public function test_store_strips_script_tags_and_event_handlers_from_body(): void
    {
        $admin = $this->makeAdmin();
        $malicious = "<p>Hello</p><script>alert('xss')</script><img src=x onerror=alert(1)>";

        $payload = $this->validPayload([
            'body' => ['ar' => $malicious, 'en' => $malicious],
        ]);

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $payload);

        $article = Article::first();

        foreach (['ar', 'en'] as $locale) {
            $stored = $article->getTranslation('body', $locale);
            $this->assertStringNotContainsString('<script', $stored);
            $this->assertStringNotContainsString('onerror', $stored);
            $this->assertStringNotContainsString('alert(', $stored);
            $this->assertStringContainsString('<p>Hello</p>', $stored);
        }
    }

    public function test_store_strips_all_markup_from_title_and_excerpt(): void
    {
        $admin = $this->makeAdmin();

        $payload = $this->validPayload([
            'title' => ['ar' => '<b>عنوان</b> <script>alert(1)</script>', 'en' => 'Title'],
            'excerpt' => ['ar' => '<i>مقتطف</i>', 'en' => 'Excerpt'],
        ]);

        $this->actingAs($admin)->post(route('dashboard.articles.store'), $payload);

        $article = Article::first();
        $this->assertSame('عنوان', $article->getTranslation('title', 'ar'));
        $this->assertSame('مقتطف', $article->getTranslation('excerpt', 'ar'));
    }

    public function test_update_modifies_an_article(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create($this->validPayload());

        $payload = $this->validPayload(['title' => ['ar' => 'عنوان جديد', 'en' => 'New title']]);
        $response = $this->actingAs($admin)->put(route('dashboard.articles.update', $article), $payload);

        $response->assertRedirect(route('dashboard.articles.index'));
        $this->assertSame('عنوان جديد', $article->fresh()->getTranslation('title', 'ar'));
    }

    public function test_update_rejects_invalid_data(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create($this->validPayload());

        $payload = $this->validPayload();
        unset($payload['body']['ar']);

        $response = $this->actingAs($admin)->put(route('dashboard.articles.update', $article), $payload);

        $response->assertSessionHasErrors('body.ar');
    }

    public function test_destroy_soft_deletes_an_article(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create($this->validPayload());

        $response = $this->actingAs($admin)->delete(route('dashboard.articles.destroy', $article));

        $response->assertRedirect(route('dashboard.articles.index'));
        $this->assertSoftDeleted($article);
    }
}
