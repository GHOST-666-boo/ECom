<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AutoCancelPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 52: Auto-Cancel Pending Orders After 48 Hours
     * 
     * For any order in 'pending' status with no successful payment after 48 hours,
     * the order should be automatically cancelled by the scheduled job.
     * 
     * **Validates: Requirements 6.8**
     */
    public function test_property_52_auto_cancel_pending_orders_after_48_hours(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            Notification::fake();
            
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $address = Address::factory()->create([
                'user_id' => $user->id,
            ]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create products with stock
            $product1 = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $product2 = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 50,
                'is_active' => true,
            ]);
            
            // Create an old pending order (48+ hours ago)
            $hoursOld = rand(49, 100);
            $oldOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_method' => 'razorpay',
                'created_at' => now()->subHours($hoursOld),
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            $quantity1 = rand(1, 5);
            $quantity2 = rand(1, 5);
            
            OrderItem::factory()->create([
                'order_id' => $oldOrder->id,
                'product_id' => $product1->id,
                'quantity' => $quantity1,
                'price' => $product1->price,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $oldOrder->id,
                'product_id' => $product2->id,
                'quantity' => $quantity2,
                'price' => $product2->price,
            ]);
            
            // Create a recent pending order (less than 48 hours) - should NOT be cancelled
            $hoursRecent = rand(1, 47);
            $recentOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_method' => 'razorpay',
                'created_at' => now()->subHours($hoursRecent),
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $recentOrder->id,
                'product_id' => $product1->id,
                'quantity' => rand(1, 3),
                'price' => $product1->price,
            ]);
            
            // Create a confirmed order (old but should NOT be cancelled)
            $confirmedOrder = Order::factory()->create([
                'user_id' => $user->id,
                'status' => 'confirmed',
                'payment_method' => 'cod',
                'created_at' => now()->subHours(60),
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $confirmedOrder->id,
                'product_id' => $product2->id,
                'quantity' => rand(1, 3),
                'price' => $product2->price,
            ]);
            
            // Run the auto-cancel command
            $exitCode = Artisan::call('orders:auto-cancel');
            
            // Verify command succeeded
            $this->assertEquals(0, $exitCode, "Auto-cancel command should succeed");
            
            // Refresh models
            $oldOrder->refresh();
            $recentOrder->refresh();
            $confirmedOrder->refresh();
            
            // Verify old pending order is cancelled
            $this->assertEquals(
                'cancelled',
                $oldOrder->status,
                "Order older than 48 hours with pending status should be cancelled"
            );
            
            // Verify recent pending order is still pending
            $this->assertEquals(
                'pending',
                $recentOrder->status,
                "Order less than 48 hours old should remain pending"
            );
            
            // Verify confirmed order is still confirmed
            $this->assertEquals(
                'confirmed',
                $confirmedOrder->status,
                "Confirmed orders should not be cancelled regardless of age"
            );
            
            // Verify cancellation notification was sent for old order
            Notification::assertSentTo(
                $user,
                OrderCancelledNotification::class,
                fn ($notification) => $notification->order->id === $oldOrder->id
                    && $notification->reason === 'payment timeout'
            );
        }
    }

    /**
     * Property 53: Stock Restored on Auto-Cancel
     * 
     * For any order automatically cancelled due to payment timeout, the product
     * stock should be incremented by the order_item quantities.
     * 
     * **Validates: Requirements 6.9**
     */
    public function test_property_53_stock_restored_on_auto_cancel(): void
    {
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            Notification::fake();
            
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            $address = Address::factory()->create([
                'user_id' => $user->id,
            ]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create products with random initial stock
            $initialStock1 = rand(10, 50);
            $initialStock2 = rand(20, 60);
            $initialStock3 = rand(5, 30);
            
            $product1 = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => $initialStock1,
                'is_active' => true,
            ]);
            
            $product2 = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => $initialStock2,
                'is_active' => true,
            ]);
            
            $product3 = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => $initialStock3,
                'is_active' => true,
            ]);
            
            // Create an old pending order with multiple items
            $hoursOld = rand(49, 120);
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_method' => 'razorpay',
                'created_at' => now()->subHours($hoursOld),
                'address_snapshot' => [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ],
            ]);
            
            // Create order items with random quantities
            $quantity1 = rand(1, 10);
            $quantity2 = rand(1, 8);
            $quantity3 = rand(1, 5);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product1->id,
                'quantity' => $quantity1,
                'price' => $product1->price,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product2->id,
                'quantity' => $quantity2,
                'price' => $product2->price,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product3->id,
                'quantity' => $quantity3,
                'price' => $product3->price,
            ]);
            
            // Run the auto-cancel command
            $exitCode = Artisan::call('orders:auto-cancel');
            
            // Verify command succeeded
            $this->assertEquals(0, $exitCode, "Auto-cancel command should succeed");
            
            // Refresh product models
            $product1->refresh();
            $product2->refresh();
            $product3->refresh();
            
            // Verify stock was restored for all products
            $this->assertEquals(
                $initialStock1 + $quantity1,
                $product1->stock,
                "Product 1 stock should be restored by {$quantity1} units"
            );
            
            $this->assertEquals(
                $initialStock2 + $quantity2,
                $product2->stock,
                "Product 2 stock should be restored by {$quantity2} units"
            );
            
            $this->assertEquals(
                $initialStock3 + $quantity3,
                $product3->stock,
                "Product 3 stock should be restored by {$quantity3} units"
            );
            
            // Verify order is cancelled
            $order->refresh();
            $this->assertEquals(
                'cancelled',
                $order->status,
                "Order should be cancelled after auto-cancel"
            );
        }
    }
}
