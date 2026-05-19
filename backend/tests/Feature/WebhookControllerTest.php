<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
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
     * Test that webhook endpoint exists and is accessible without authentication.
     */
    public function test_webhook_endpoint_is_accessible_without_authentication(): void
    {
        $webhookPayload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'notes' => [
                            'order_id' => 999999,
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        // Send webhook without authentication
        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        // Should not return 401 (authentication required)
        $this->assertNotEquals(401, $response->status());
    }

    /**
     * Test successful payment webhook updates order status.
     */
    public function test_successful_payment_webhook_updates_order_status(): void
    {
        // Create an order
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('order.id');
        $paymentId = 'pay_test123';

        // Verify order is pending
        $order = Order::find($orderId);
        $this->assertEquals('pending', $order->status);

        // Create successful payment webhook
        $webhookPayload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
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

        // Send webhook
        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Payment processed successfully',
        ]);

        // Verify order status updated
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals($paymentId, $order->payment_id);
    }

    /**
     * Test failed payment webhook keeps order pending.
     */
    public function test_failed_payment_webhook_keeps_order_pending(): void
    {
        // Create an order
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('order.id');

        // Create failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'failed',
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

        // Send webhook
        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Payment failure recorded',
        ]);

        // Verify order status remains pending
        $order = Order::find($orderId);
        $this->assertEquals('pending', $order->status);
        $this->assertNull($order->payment_id);
    }

    /**
     * Test invalid webhook signature is rejected.
     */
    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $webhookPayload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'notes' => [
                            'order_id' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Generate invalid signature
        $invalidSignature = hash_hmac('sha256', json_encode($webhookPayload), 'wrong_secret');

        // Send webhook with invalid signature
        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $invalidSignature,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid webhook signature',
        ]);
    }

    /**
     * Test duplicate webhook is handled idempotently.
     */
    public function test_duplicate_webhook_is_handled_idempotently(): void
    {
        // Create an order
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('order.id');
        $paymentId = 'pay_test123';

        // Create webhook payload
        $webhookPayload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
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

        // Send webhook first time
        $response1 = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'message' => 'Payment processed successfully',
        ]);

        // Send same webhook again
        $response2 = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'message' => 'Webhook already processed',
        ]);

        // Verify order is still confirmed
        $order = Order::find($orderId);
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals($paymentId, $order->payment_id);
    }

    /**
     * Test webhook with missing order_id is rejected.
     */
    public function test_webhook_with_missing_order_id_is_rejected(): void
    {
        $webhookPayload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'notes' => [
                            // Missing order_id
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Order ID not found in webhook payload',
        ]);
    }

    /**
     * Test webhook with non-existent order is rejected.
     */
    public function test_webhook_with_non_existent_order_is_rejected(): void
    {
        $webhookPayload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'notes' => [
                            'order_id' => 999999, // Non-existent order
                        ],
                    ],
                ],
            ],
        ];

        $payloadJson = json_encode($webhookPayload);
        $webhookSecret = config('services.razorpay.webhook_secret');
        $validSignature = $this->generateWebhookSignature($payloadJson, $webhookSecret);

        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Order not found',
        ]);
    }

    /**
     * Test failed payment webhook restores cart with order items.
     */
    public function test_failed_payment_webhook_restores_cart(): void
    {
        // Create an order
        $user = User::factory()->create(['email_verified_at' => now()]);
        $address = Address::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 100,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $response->json('order.id');

        // Verify cart is empty after order placement
        $this->assertEquals(0, CartItem::where('cart_id', $cart->id)->count());

        // Create failed payment webhook
        $webhookPayload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test123',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'failed',
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

        // Send webhook
        $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
            'X-Razorpay-Signature' => $validSignature,
        ]);

        $response->assertStatus(200);

        // Verify cart is restored with order items
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        $this->assertEquals(1, $cartItems->count());
        $this->assertEquals($product->id, $cartItems->first()->product_id);
        $this->assertEquals(2, $cartItems->first()->quantity);
    }
}
