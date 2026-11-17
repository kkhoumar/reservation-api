<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => fn (array $attributes) => User::factory()->cashier()->state([
                'restaurant_id' => Order::withoutGlobalScopes()->findOrFail($attributes['order_id'])->restaurant_id,
            ]),
            'payment_method' => 'cash',
            'amount_due' => 5000,
            'amount_received' => 5000,
            'change_given' => 0,
            'reference' => null,
            'status' => 'completed',
            'paid_at' => now(),
        ];
    }
}
