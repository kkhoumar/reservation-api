<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Zone>
 */
class ZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->randomElement(['Terrasse', 'Salle', 'VIP']),
        ];
    }
}
