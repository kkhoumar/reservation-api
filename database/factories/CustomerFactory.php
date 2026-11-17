<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->firstName(),
            'phone' => '+225 01 ' . fake()->numerify('## ## ## ##'),
            'note' => null,
        ];
    }
}
