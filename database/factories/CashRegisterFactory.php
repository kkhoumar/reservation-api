<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'opened_by' => fn (array $attributes) => User::factory()->cashier()->state(['restaurant_id' => $attributes['restaurant_id']]),
            'opening_amount' => 20000,
            'opened_at' => now()->subHours(2),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_by' => $attributes['opened_by'],
            'theoretical_amount' => 20000,
            'counted_amount' => 20000,
            'discrepancy' => 0,
            'closed_at' => now()->subHour(),
        ]);
    }
}
