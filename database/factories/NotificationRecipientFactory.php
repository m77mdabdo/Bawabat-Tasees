<?php

namespace Database\Factories;

use App\Models\NotificationRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationRecipient> */
class NotificationRecipientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'receives_consultations' => true,
            'receives_contact_messages' => true,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
