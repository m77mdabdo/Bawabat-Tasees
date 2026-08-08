<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'phone' => '+9665'.$this->faker->numerify('########'),
            'email' => $this->faker->unique()->safeEmail(),
            'type' => 'consultation',
            'source_platform' => 'google',
            'landing_page_url' => '/consultation',
            'consent_given' => true,
            'consented_at' => now(),
        ];
    }

    public function contact(): static
    {
        return $this->state(fn () => ['type' => 'contact']);
    }

    public function fromCampaign(string $externalId): static
    {
        return $this->state(fn () => [
            'campaign_id' => $externalId,
            'utm_campaign' => $externalId,
        ]);
    }
}
