<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 50 orders with various statuses distributed across customer users.
     */
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        
        if ($customers->isEmpty()) {
            $this->command->error('No customer users found. Please run UserSeeder first.');
            return;
        }

        $products = Product::where('is_active', true)->get();
        
        if ($products->isEmpty()) {
            $this->command->error('No active products found. Please run ProductSeeder first.');
            return;
        }

        // Status distribution for realistic data
        $statusDistribution = [
            'pending' => 5,      // 10%
            'confirmed' => 15,   // 30%
            'shipped' => 12,     // 24%
            'delivered' => 15,   // 30%
            'cancelled' => 3,    // 6%
        ];

        $orderNumber = 1;
        $ordersCreated = 0;

        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                // Select a random customer
                $customer = $customers->random();

                // Generate order number
                $orderNum = 'ORD-' . date('Y') . str_pad($orderNumber++, 5, '0', STR_PAD_LEFT);

                // Generate address snapshot
                $addressSnapshot = [
                    'name' => $customer->name,
                    'line1' => fake()->streetAddress(),
                    'line2' => fake()->optional(0.3)->secondaryAddress(),
                    'city' => fake()->randomElement(['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Chennai', 'Kolkata', 'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow']),
                    'state' => fake()->randomElement(['Maharashtra', 'Delhi', 'Karnataka', 'Telangana', 'Tamil Nadu', 'West Bengal', 'Gujarat', 'Rajasthan', 'Uttar Pradesh']),
                    'pincode' => fake()->numerify('######'),
                ];

                // Select 1-5 random products for this order
                $orderProducts = $products->random(fake()->numberBetween(1, 5));
                
                // Calculate total
                $total = 0;
                $orderItemsData = [];
                
                foreach ($orderProducts as $product) {
                    $quantity = fake()->numberBetween(1, 3);
                    $price = $product->price;
                    $total += $quantity * $price;
                    
                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price,
                    ];
                }

                // Determine payment method
                $paymentMethod = fake()->randomElement(['cod', 'razorpay']);
                $paymentId = $paymentMethod === 'razorpay' ? 'pay_' . fake()->uuid() : null;

                // Create order with appropriate created_at based on status
                $createdAt = match($status) {
                    'pending' => now()->subHours(fake()->numberBetween(1, 47)), // Within 48 hours
                    'confirmed' => now()->subDays(fake()->numberBetween(1, 7)),
                    'shipped' => now()->subDays(fake()->numberBetween(3, 14)),
                    'delivered' => now()->subDays(fake()->numberBetween(7, 60)),
                    'cancelled' => now()->subDays(fake()->numberBetween(1, 30)),
                };

                $order = Order::create([
                    'user_id' => $customer->id,
                    'order_number' => $orderNum,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'payment_id' => $paymentId,
                    'total' => $total,
                    'address_snapshot' => $addressSnapshot,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Create order items
                foreach ($orderItemsData as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                $ordersCreated++;
            }
        }

        $this->command->info("Created {$ordersCreated} orders with various statuses:");
        $this->command->info("  - Pending: {$statusDistribution['pending']}");
        $this->command->info("  - Confirmed: {$statusDistribution['confirmed']}");
        $this->command->info("  - Shipped: {$statusDistribution['shipped']}");
        $this->command->info("  - Delivered: {$statusDistribution['delivered']}");
        $this->command->info("  - Cancelled: {$statusDistribution['cancelled']}");
    }
}
