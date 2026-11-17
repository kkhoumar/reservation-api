<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            // Le produit appartient au même restaurant que la commande
            'product_id' => fn (array $attributes) => Product::factory([
                'restaurant_id' => Order::withoutGlobalScopes()->findOrFail($attributes['order_id'])->restaurant_id,
            ]),
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
            'note' => null,
        ];
    }
}
