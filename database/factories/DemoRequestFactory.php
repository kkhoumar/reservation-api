<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DemoRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contact_name' => fake()->firstName() . ' ' . fake()->lastName(),
            'restaurant_name' => 'Maquis ' . fake()->lastName(),
            'phone' => '07' . fake()->unique()->numerify('########'),
            'commune' => fake()->randomElement(['Yopougon', 'Cocody', 'Marcory', 'Abobo']),
            'message' => null,
        ];
    }
}
