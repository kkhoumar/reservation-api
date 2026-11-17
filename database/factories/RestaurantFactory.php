<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Maquis ' . fake()->unique()->lastName(),
            'type' => 'maquis',
            'phone' => '+225 07 ' . fake()->unique()->numerify('## ## ## ##'),
            'commune' => 'Cocody',
            'city' => 'Abidjan',
            'subscription_status' => 'active',
            'trial_ends_at' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['subscription_status' => 'suspended']);
    }
}
