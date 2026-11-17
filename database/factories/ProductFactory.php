<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            // La catégorie appartient toujours au même restaurant que le produit
            'category_id' => fn (array $attributes) => Category::factory(['restaurant_id' => $attributes['restaurant_id']]),
            'name' => fake()->randomElement(['Flag', 'Bock', 'Poulet braisé', 'Garba', 'Attiéké poisson']),
            'selling_price' => 1000,
            'is_available' => true,
            'stock_quantity' => 50,
            'stock_alert_threshold' => null,
        ];
    }
}
