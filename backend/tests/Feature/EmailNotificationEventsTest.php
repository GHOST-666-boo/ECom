<?php

namespace Tests\Feature;

use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationEventsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that OrderPlaced event is dispatched when order is placed.
     */
    public function test_order_placed_event_dispatched_on_order_creation(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 10, 'price' => 100]);
        
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/orders', [
            'address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(201);
        Event::assertDispatched(OrderPlaced::class);
    }

    /**
     * Test that OrderPlaced notification is sent when event is dispatched.
     */
    public function test_order_placed_notification_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        event(new OrderPlaced($order));

        Notification::assertSentTo($user, OrderPlacedNotification::class);
    }

    /**
     * Test that OrderShipped event is dispatched when admin updates status to shipped.
     */
    public function test_order_shipped_event_dispatched_on_status_update(): void
    {
        Event::fake([OrderShipped::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/orders/{$order->id}/status", [
            'status'          => 'shipped',
            'tracking_number' => 'TRACK123456789',
            'courier_name'    => 'BlueDart',
        ]);

        $response->assertStatus(200);
        Event::assertDispatched(OrderShipped::class);
    }

    /**
     * Test that OrderShipped notification is sent when event is dispatched.
     */
    public function test_order_shipped_notification_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        event(new OrderShipped($order));

        Notification::assertSentTo($user, OrderShippedNotification::class);
    }

    /**
     * Test that OrderDelivered event is dispatched when admin updates status to delivered.
     */
    public function test_order_delivered_event_dispatched_on_status_update(): void
    {
        Event::fake([OrderDelivered::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'shipped']);

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'delivered',
        ]);

        $response->assertStatus(200);
        Event::assertDispatched(OrderDelivered::class);
    }

    /**
     * Test that OrderDelivered notification is sent when event is dispatched.
     */
    public function test_order_delivered_notification_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        event(new OrderDelivered($order));

        Notification::assertSentTo($user, OrderDeliveredNotification::class);
    }

    /**
     * Test that OrderCancelled event is dispatched when customer cancels order.
     */
    public function test_order_cancelled_event_dispatched_on_customer_cancel(): void
    {
        Event::fake([OrderCancelled::class]);

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200);
        Event::assertDispatched(OrderCancelled::class);
    }

    /**
     * Test that OrderCancelled notification is sent when event is dispatched.
     */
    public function test_order_cancelled_notification_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        event(new OrderCancelled($order, 'customer request'));

        Notification::assertSentTo($user, OrderCancelledNotification::class);
    }

    /**
     * Test that OrderCancelled event is dispatched when admin cancels order.
     */
    public function test_order_cancelled_event_dispatched_on_admin_cancel(): void
    {
        Event::fake([OrderCancelled::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200);
        Event::assertDispatched(OrderCancelled::class);
    }
}
