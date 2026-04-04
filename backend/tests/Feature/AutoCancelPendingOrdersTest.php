<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AutoCancelPendingOrdersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that pending orders older than 48 hours are cancelled.
     */
    public function test_auto_cancel_pending_orders_older_than_48_hours(): void
    {
        Notification::fake();

        // Create a user
        $user = User::factory()->create();

        // Create a product with stock
        $product = Product::factory()->create(['stock' => 10]);

        // Create an old pending order (49 hours ago)
        $oldOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now()->subHours(49),
        ]);

        OrderItem::factory()->create([
            'order_id' => $oldOrder->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => $product->price,
        ]);

        // Create a recent pending order (24 hours ago) - should NOT be cancelled
        $recentOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now()->subHours(24),
        ]);

        OrderItem::factory()->create([
            'order_id' => $recentOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        // Run the command
        Artisan::call('orders:auto-cancel');

        // Refresh models
        $oldOrder->refresh();
        $recentOrder->refresh();
        $product->refresh();

        // Assert old order is cancelled
        $this->assertEquals('cancelled', $oldOrder->status);

        // Assert recent order is still pending
        $this->assertEquals('pending', $recentOrder->status);

        // Assert stock was restored for old order (10 + 3 = 13)
        $this->assertEquals(13, $product->stock);

        // Assert notification was sent for old order
        Notification::assertSentTo(
            $user,
            OrderCancelledNotification::class,
            function ($notification) use ($oldOrder) {
                return $notification->order->id === $oldOrder->id
                    && $notification->reason === 'payment timeout';
            }
        );
    }

    /**
     * Test that confirmed orders are not cancelled.
     */
    public function test_confirmed_orders_are_not_cancelled(): void
    {
        Notification::fake();

        // Create a user
        $user = User::factory()->create();

        // Create a product
        $product = Product::factory()->create(['stock' => 10]);

        // Create an old confirmed order (49 hours ago)
        $confirmedOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
            'created_at' => now()->subHours(49),
        ]);

        OrderItem::factory()->create([
            'order_id' => $confirmedOrder->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => $product->price,
        ]);

        // Run the command
        Artisan::call('orders:auto-cancel');

        // Refresh models
        $confirmedOrder->refresh();
        $product->refresh();

        // Assert order is still confirmed
        $this->assertEquals('confirmed', $confirmedOrder->status);

        // Assert stock was not restored
        $this->assertEquals(10, $product->stock);

        // Assert no notification was sent
        Notification::assertNothingSent();
    }

    /**
     * Test that stock is restored for multiple order items.
     */
    public function test_stock_restored_for_multiple_order_items(): void
    {
        Notification::fake();

        // Create a user
        $user = User::factory()->create();

        // Create multiple products
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 20]);

        // Create an old pending order with multiple items
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now()->subHours(49),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 3,
            'price' => $product1->price,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 5,
            'price' => $product2->price,
        ]);

        // Run the command
        Artisan::call('orders:auto-cancel');

        // Refresh models
        $product1->refresh();
        $product2->refresh();

        // Assert stock was restored for both products
        $this->assertEquals(13, $product1->stock); // 10 + 3
        $this->assertEquals(25, $product2->stock); // 20 + 5
    }

    /**
     * Test that command handles no pending orders gracefully.
     */
    public function test_command_handles_no_pending_orders(): void
    {
        // Run the command with no orders in database
        $exitCode = Artisan::call('orders:auto-cancel');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);
    }
}
