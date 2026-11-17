<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'table_id' => null,
            'user_id' => fn (array $attributes) => User::factory()->server()->state(['restaurant_id' => $attributes['restaurant_id']]),
            'order_number' => 'CMD-' . fake()->unique()->numerify('######'),
            'order_type' => 'takeaway',
            'status' => 'created',
            'subtotal' => 0,
            'total' => 0,
        ];
    }

    public function awaitingPayment(int $total = 5000): static
    {
        return $this->state(['status' => 'awaiting_payment', 'subtotal' => $total, 'total' => $total]);
    }
}
