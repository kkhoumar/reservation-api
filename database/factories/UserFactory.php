<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '+225 05 ' . fake()->unique()->numerify('## ## ## ##'),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => 'owner']);
    }

    public function manager(): static
    {
        return $this->state(['role' => 'manager']);
    }

    public function cashier(): static
    {
        return $this->state(['role' => 'cashier']);
    }

    public function server(): static
    {
        return $this->state(['role' => 'server']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
