<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Country;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Real photographs for the seeded services and articles, and country flags.
 *
 * Files live in database/seeders/photos (so they ship with the code; storage/ is not in git)
 * and are copied to the public disk under photos/. A record only gets a photo when it has none
 * or still has one of the old generated green placeholders (covers/service-N, covers/article-N):
 * anything uploaded in the dashboard is never replaced. Safe to run any number of times.
 * Photo sources and licences: docs/photo-credits.md.
 */
class PhotoSeeder extends Seeder
{
    private const OLD_PLACEHOLDER = '#^covers/(service|article)-\d+\.jpg$#';

    public function run(): void
    {
        $this->assign(Service::class, 'services', 'cover_image');
        $this->assign(Article::class, 'articles', 'cover_image');
        $this->assign(Country::class, 'flags', 'flag_image');
    }

    /** @param  class-string<Model>  $model */
    private function assign(string $model, string $folder, string $column): void
    {
        foreach (glob(database_path("seeders/photos/{$folder}/*.*")) as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $record = $model::query()->where('slug', $slug)->first();
            $current = $record?->{$column};

            if (! $record || ($current && ! preg_match(self::OLD_PLACEHOLDER, $current))) {
                continue;
            }

            $path = "photos/{$folder}/".basename($file);
            Storage::disk('public')->put($path, file_get_contents($file));
            $record->forceFill([$column => $path])->saveQuietly();
        }
    }
}
