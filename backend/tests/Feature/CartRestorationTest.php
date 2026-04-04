<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CartRestorationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that cart is restored when payment fails.
     */
    public function test_cart_is_restored_on_payment_failure(): void
    {
        Notification::fake();

        // Create user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create category and products
        $category = Category::factory()->create();
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'price' => 100.00,
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 5,
            'price' => 50.00,
        ]);

        // Create order with items
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'razorpay',
            'total' => 250.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 50.00,
        ]);

        // Simulate webhook payload for failed payment
        $payload = json_encode([
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'notes' => [
                            'order_id' => $order->id,
                        ],
                        'error_description' => 'Payment declined by bank',
                    ],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, config('services.razorpay.webhook_secret'));

        // Send webhook request
        $response = $this->postJson('/api/v1/webhooks/razorpay', json_decode($payload, true), [
            'X-Razorpay-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Payment failure recorded',
        ]);

        // Verify cart was restored
        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);

        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        $this->assertCount(2, $cartItems);

        // Verify cart items match order items
        $cartItem1 = $cartItems->where('product_id', $product1->id)->first();
        $this->assertNotNull($cartItem1);
        $this->assertEquals(2, $cartItem1->quantity);

        $cartItem2 = $cartItems->where('product_id', $product2->id)->first();
        $this->assertNotNull($cartItem2);
        $this->assertEquals(1, $cartItem2->quantity);

        // Verify notification was sent
        Notification::assertSentTo($user, PaymentFailedNotification::class);
    }

    /**
     * Test that out-of-stock products are skipped during cart restoration.
     */
    public function test_out_of_stock_products_are_skipped_on_cart_restoration(): void
    {
        Notification::fake();

        // Create user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create category and products
        $category = Category::factory()->create();
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'price' => 100.00,
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 0, // Out of stock
            'price' => 50.00,
        ]);

        // Create order with items
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'razorpay',
            'total' => 250.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 50.00,
        ]);

        // Simulate webhook payload for failed payment
        $payload = json_encode([
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'notes' => [
                            'order_id' => $order->id,
                        ],
                        'error_description' => 'Payment declined by bank',
                    ],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, config('services.razorpay.webhook_secret'));

        // Send webhook request
        $response = $this->postJson('/api/v1/webhooks/razorpay', json_decode($payload, true), [
            'X-Razorpay-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Verify cart was restored with only in-stock product
        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);

        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        $this->assertCount(1, $cartItems); // Only product1 should be restored

        // Verify only in-stock product is in cart
        $cartItem1 = $cartItems->where('product_id', $product1->id)->first();
        $this->assertNotNull($cartItem1);
        $this->assertEquals(2, $cartItem1->quantity);

        // Verify out-of-stock product is not in cart
        $cartItem2 = $cartItems->where('product_id', $product2->id)->first();
        $this->assertNull($cartItem2);

        // Verify notification was sent with skipped items
        Notification::assertSentTo($user, PaymentFailedNotification::class, function ($notification) use ($product2) {
            return count($notification->skippedItems) === 1 &&
                   $notification->skippedItems[0]['name'] === $product2->name;
        });
    }
}
