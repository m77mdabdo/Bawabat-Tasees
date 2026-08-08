<?php

namespace Database\Factories;

use App\Models\TrackingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingSetting>
 */
class TrackingSettingFactory extends Factory
{
    protected $model = TrackingSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => $this->faker->numerify('###############'),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }
}
