<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'platform' => 'google',
            // Unique: it is the key AttributionService matches leads on.
            'external_campaign_id' => $this->faker->unique()->bothify('camp-####'),
            'budget' => 20000,
            'spend' => 10000,
            'currency' => 'SAR',
            'is_active' => true,
        ];
    }

    public function withoutSpend(): static
    {
        return $this->state(fn () => ['budget' => null, 'spend' => null]);
    }
}
