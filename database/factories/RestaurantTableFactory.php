<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantTable>
 */
class RestaurantTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'zone_id' => null,
            'label' => 'T' . fake()->unique()->numberBetween(1, 999),
            'capacity' => 4,
            'status' => 'free',
        ];
    }
}
