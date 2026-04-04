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

/**
 * Property-Based Tests for Cart Restoration on Payment Failure
 * 
 * These tests validate universal properties that should hold across all inputs.
 */
class CartRestorationPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate a valid Razorpay webhook signature.
     */
    private function generateWebhookSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * **Validates: Requirements 6.5**
     * 
     * Property: For any failed payment, the cart should be restored with the order items.
     */
    public function test_failed_payment_restores_cart_with_order_items(): void
    {
        Notification::fake();

        // Create user and products
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
            'price' => 1000,
        ]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 50,
            'is_active' => true,
            'price' => 2000,
        ]);

        // Create cart and add items
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 3,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);

        // Place order (this clears the cart)
        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('data.order.id');

        // Verify cart is empty after order placement
        $this->assertEquals(0, CartItem::where('cart_id', $cart->id)->count());

        // Simulate failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'error_description' => 'Payment failed',
                        'notes' => [
                            'order_id' => $orderId,
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        // Verify cart is restored with order items
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        $this->assertEquals(2, $cartItems->count());

        $item1 = $cartItems->firstWhere('product_id', $product1->id);
        $this->assertNotNull($item1);
        $this->assertEquals(3, $item1->quantity);

        $item2 = $cartItems->firstWhere('product_id', $product2->id);
        $this->assertNotNull($item2);
        $this->assertEquals(2, $item2->quantity);
    }

    /**
     * **Validates: Requirements 6.6**
     * 
     * Property: For any cart restoration, out-of-stock products should be skipped
     * and the customer should be notified.
     */
    public function test_out_of_stock_items_skipped_during_cart_restoration(): void
    {
        Notification::fake();

        // Create user and products
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        
        $inStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
            'price' => 1000,
        ]);
        
        $outOfStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 5,
            'is_active' => true,
            'price' => 2000,
        ]);

        // Create cart and add items
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $inStockProduct->id,
            'quantity' => 2,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $outOfStockProduct->id,
            'quantity' => 3,
        ]);

        // Place order
        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('data.order.id');

        // Make the second product out of stock
        $outOfStockProduct->update(['stock' => 0]);

        // Simulate failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'error_description' => 'Payment failed',
                        'notes' => [
                            'order_id' => $orderId,
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        // Verify only in-stock product is restored
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        $this->assertEquals(1, $cartItems->count());

        $restoredItem = $cartItems->first();
        $this->assertEquals($inStockProduct->id, $restoredItem->product_id);
        $this->assertEquals(2, $restoredItem->quantity);

        // Verify notification was sent with skipped items
        Notification::assertSentTo(
            $user,
            PaymentFailedNotification::class,
            function ($notification) use ($outOfStockProduct) {
                return count($notification->skippedItems) === 1 &&
                       $notification->skippedItems[0]['name'] === $outOfStockProduct->name;
            }
        );
    }

    /**
     * **Validates: Requirements 6.5**
     * 
     * Property: For any failed payment, a notification email should be sent
     * about the failed payment and cart restoration.
     */
    public function test_notification_sent_on_cart_restoration(): void
    {
        Notification::fake();

        // Create user and product
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
            'price' => 1000,
        ]);

        // Create cart and add item
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Place order
        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('data.order.id');

        // Simulate failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'error_description' => 'Payment failed',
                        'notes' => [
                            'order_id' => $orderId,
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        // Verify notification was sent
        Notification::assertSentTo(
            $user,
            PaymentFailedNotification::class,
            function ($notification) use ($orderId) {
                $order = Order::find($orderId);
                return $notification->order->id === $order->id &&
                       count($notification->restoredItems) > 0;
            }
        );
    }

    /**
     * **Validates: Requirements 6.5**
     * 
     * Property: When restoring cart with existing items, quantities should be
     * incremented rather than creating duplicates.
     */
    public function test_cart_restoration_increments_existing_items(): void
    {
        Notification::fake();

        // Create user and product
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
            'price' => 1000,
        ]);

        // Create cart and add item
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Place order
        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('data.order.id');

        // Add the same product back to cart before payment fails
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Simulate failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'error_description' => 'Payment failed',
                        'notes' => [
                            'order_id' => $orderId,
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        // Verify only one cart item exists with incremented quantity
        $cartItems = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->get();
        
        $this->assertEquals(1, $cartItems->count());
        $this->assertEquals(5, $cartItems->first()->quantity); // 2 + 3
    }

    /**
     * **Validates: Requirements 6.5, 6.6**
     * 
     * Property: Cart restoration should respect stock limits when incrementing quantities.
     */
    public function test_cart_restoration_respects_stock_limits(): void
    {
        Notification::fake();

        // Create user and product with limited stock
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'is_active' => true,
            'price' => 1000,
        ]);

        // Create cart and add item
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        // Place order
        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('data.order.id');

        // Add the same product back to cart with quantity that would exceed stock
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Simulate failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'error_description' => 'Payment failed',
                        'notes' => [
                            'order_id' => $orderId,
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        // Verify quantity is capped at stock limit
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();
        
        $this->assertNotNull($cartItem);
        $this->assertEquals(10, $cartItem->quantity); // Capped at stock limit, not 5 + 8 = 13
    }
}