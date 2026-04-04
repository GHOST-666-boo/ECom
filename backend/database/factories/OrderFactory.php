<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . date('Y') . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => fake()->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']),
            'payment_method' => fake()->randomElement(['cod', 'razorpay']),
            'payment_id' => fake()->optional()->uuid(),
            'total' => fake()->randomFloat(2, 10, 1000),
            'address_snapshot' => [
                'name' => fake()->name(),
                'line1' => fake()->streetAddress(),
                'line2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'pincode' => fake()->numerify('######'),
            ],
        ];
    }
}
