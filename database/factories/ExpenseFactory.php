<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'user_id' => fn (array $attributes) => User::factory()->manager()->state(['restaurant_id' => $attributes['restaurant_id']]),
            'cash_register_id' => null,
            'category' => 'other',
            'amount' => 2500,
            'description' => 'Glace',
            'expense_date' => now(),
        ];
    }
}
