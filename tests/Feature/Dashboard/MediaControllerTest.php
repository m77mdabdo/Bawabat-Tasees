<?php

namespace Tests\Feature\Dashboard;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard.media.index'));

        $response->assertOk();
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.media.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.media.index'))->assertForbidden();
    }

    public function test_upload_image_creates_media_row_and_stores_file(): void
    {
        $admin = $this->makeAdmin();
        $file = UploadedFile::fake()->image('photo.jpg', 200, 200)->size(500);

        $response = $this->actingAs($admin)->post(route('dashboard.media.store'), [
            'file' => $file,
            'alt_text' => 'A test photo',
        ]);

        $response->assertRedirect(route('dashboard.media.index'));
        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertSame('image', $media->type);
        $this->assertSame('A test photo', $media->alt_text);
        $this->assertSame($admin->id, $media->uploaded_by);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_video_creates_media_row_and_stores_file(): void
    {
        $admin = $this->makeAdmin();
        $file = UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4');

        $response = $this->actingAs($admin)->post(route('dashboard.media.store'), [
            'file' => $file,
            'alt_text' => 'A test clip',
        ]);

        $response->assertRedirect(route('dashboard.media.index'));
        $media = Media::first();
        $this->assertNotNull($media);
        $this->assertSame('video', $media->type);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_rejects_invalid_mime_type(): void
    {
        $admin = $this->makeAdmin();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('dashboard.media.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_upload_rejects_oversized_image(): void
    {
        $admin = $this->makeAdmin();
        // fake()->image() size is in kilobytes; 6MB > the 5MB image limit.
        $file = UploadedFile::fake()->image('big.jpg')->size(6 * 1024);

        $response = $this->actingAs($admin)->post(route('dashboard.media.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_destroy_removes_db_row_and_disk_file(): void
    {
        $admin = $this->makeAdmin();
        $file = UploadedFile::fake()->image('photo.jpg');
        $this->actingAs($admin)->post(route('dashboard.media.store'), ['file' => $file]);

        $media = Media::first();
        $path = $media->path;
        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($admin)->delete(route('dashboard.media.destroy', $media));

        $response->assertRedirect(route('dashboard.media.index'));
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
