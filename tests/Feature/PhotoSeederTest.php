<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Country;
use App\Models\Service;
use Database\Seeders\PhotoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_content_gets_real_photos_and_flags(): void
    {
        Storage::fake('public');
        $service = Service::factory()->create(['slug' => 'llc-formation', 'cover_image' => 'covers/service-1.jpg']);
        $article = Article::factory()->create(['slug' => 'foreign-ownership-rules-explained', 'cover_image' => null]);
        $country = Country::factory()->create(['slug' => 'egypt', 'flag_image' => null]);

        $this->seed(PhotoSeeder::class);

        $this->assertSame('photos/services/llc-formation.webp', $service->fresh()->cover_image);
        $this->assertSame('photos/articles/foreign-ownership-rules-explained.webp', $article->fresh()->cover_image);
        $this->assertSame('photos/flags/egypt.png', $country->fresh()->flag_image);
        Storage::disk('public')->assertExists(['photos/services/llc-formation.webp', 'photos/flags/egypt.png']);
    }

    public function test_an_image_uploaded_in_the_dashboard_is_never_replaced(): void
    {
        Storage::fake('public');
        $service = Service::factory()->create(['slug' => 'llc-formation', 'cover_image' => 'services/own-photo.jpg']);

        $this->seed(PhotoSeeder::class);
        $this->seed(PhotoSeeder::class);

        $this->assertSame('services/own-photo.jpg', $service->fresh()->cover_image);
    }

    public function test_every_shipped_photo_belongs_to_a_seeded_record(): void
    {
        $seeded = file_get_contents(database_path('seeders/ServiceSeeder.php')).file_get_contents(database_path('seeders/ArticleSeeder.php')).file_get_contents(database_path('seeders/CountrySeeder.php'));

        foreach (glob(database_path('seeders/photos/*/*.*')) as $file) {
            $this->assertStringContainsString("'".pathinfo($file, PATHINFO_FILENAME)."'", $seeded, basename($file).' matches no seeded slug.');
        }
    }
}
