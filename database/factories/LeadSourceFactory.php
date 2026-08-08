<?php

namespace Database\Factories;

use App\Models\LeadSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadSource>
 */
class LeadSourceFactory extends Factory
{
    protected $model = LeadSource::class;

    public function definition(): array
    {
        $key = $this->faker->unique()->slug(1);

        return [
            'key' => $key,
            'label' => ucfirst($key),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
