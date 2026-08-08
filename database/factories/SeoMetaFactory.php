<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    protected $model = SeoMeta::class;

    public function definition(): array
    {
        // Defaults to a Page owner; override seo_metable_type/id or use
        // $model->seoMeta()->save(SeoMeta::factory()->make()) for others.
        return [
            'seo_metable_type' => Page::class,
            'seo_metable_id' => Page::factory(),
            'meta_title' => ['ar' => 'عنوان ميتا', 'en' => $this->faker->sentence(4)],
            'meta_description' => ['ar' => 'وصف ميتا', 'en' => $this->faker->sentence()],
        ];
    }
}
