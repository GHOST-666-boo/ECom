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
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RazorpayWebhookPropertiesTest extends TestCase
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
     * Property 47: Razorpay Webhook Signature Verification
     * 
     * For any Razorpay webhook notification, the webhook signature should be
     * verified using HMAC SHA-256 before processing the payment.
     * 
     * **Validates: Requirements 6.2**
     */
    public function test_property_47_razorpay_webhook_signature_verification(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create an order
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create(['slug' => 'category-' . uniqid() . '-' . $i]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            
            // Create webhook payload
            $webhookPayload = [
                'event' => 'payment.captured',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_' . uniqid(),
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
            
            // Send webhook with valid signature
            $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
                'X-Razorpay-Signature' => $validSignature,
            ]);
            
            // Webhook should be accepted
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
            ]);
            
            // Verify order status was updated
            $order = Order::find($orderId);
            $this->assertEquals('confirmed', $order->status);
        }
    }

    /**
     * Property 48: Successful Payment Updates Order Status
     * 
     * For any verified Razorpay webhook with successful payment status, the
     * corresponding order status should be updated to 'confirmed'.
     * 
     * **Validates: Requirements 6.3**
     */
    public function test_property_48_successful_payment_updates_order_status(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create an order
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create(['slug' => 'category-' . uniqid() . '-' . $i]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            $paymentId = 'pay_' . uniqid();
            
            // Verify order is initially pending
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
            
            // Verify order status updated to confirmed
            $order->refresh();
            $this->assertEquals('confirmed', $order->status);
            $this->assertEquals($paymentId, $order->payment_id);
        }
    }

    /**
     * Property 49: Failed Payment Keeps Order Pending
     * 
     * For any failed payment, the order status should remain 'pending' to
     * allow payment retry.
     * 
     * **Validates: Requirements 6.4**
     */
    public function test_property_49_failed_payment_keeps_order_pending(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create an order
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create(['slug' => 'category-' . uniqid() . '-' . $i]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            
            // Verify order is initially pending
            $order = Order::find($orderId);
            $this->assertEquals('pending', $order->status);
            
            // Create failed payment webhook
            $webhookPayload = [
                'event' => 'payment.failed',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_' . uniqid(),
                            'amount' => 50000,
                            'currency' => 'INR',
                            'status' => 'failed',
                            'error_description' => 'Payment failed due to insufficient funds',
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
            
            // Verify order status remains pending
            $order->refresh();
            $this->assertEquals('pending', $order->status);
            $this->assertNull($order->payment_id);
        }
    }

    /**
     * Property 54: No Sensitive Payment Data Stored
     * 
     * For any order with Razorpay payment, only the order_id and payment_id
     * should be stored; no credit card numbers, CVV, or other sensitive
     * payment details should be stored.
     * 
     * **Validates: Requirements 6.10, 6.11**
     */
    public function test_property_54_no_sensitive_payment_data_stored(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create an order
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create(['slug' => 'category-' . uniqid() . '-' . $i]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            $paymentId = 'pay_' . uniqid();
            
            // Create webhook with sensitive data (simulating real Razorpay webhook)
            $webhookPayload = [
                'event' => 'payment.captured',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => $paymentId,
                            'amount' => 50000,
                            'currency' => 'INR',
                            'status' => 'captured',
                            'card' => [
                                'last4' => '1234',
                                'network' => 'Visa',
                                'type' => 'credit',
                            ],
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
            $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
                'X-Razorpay-Signature' => $validSignature,
            ]);
            
            // Verify only payment_id is stored, no sensitive data
            $order = Order::find($orderId);
            $this->assertEquals($paymentId, $order->payment_id);
            
            // Verify no sensitive payment data in database
            $orderArray = $order->toArray();
            $this->assertArrayNotHasKey('card', $orderArray);
            $this->assertArrayNotHasKey('cvv', $orderArray);
            $this->assertArrayNotHasKey('card_number', $orderArray);
            
            // Verify only expected fields exist
            $this->assertArrayHasKey('payment_id', $orderArray);
            $this->assertArrayHasKey('payment_method', $orderArray);
            $this->assertEquals('razorpay', $order->payment_method);
        }
    }

    /**
     * Property 55: Idempotent Webhook Processing
     * 
     * For any payment webhook on an order already in 'confirmed' status, the
     * webhook should not process again (idempotency check).
     * 
     * **Validates: Requirements 6.12**
     */
    public function test_property_55_idempotent_webhook_processing(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create an order
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create(['slug' => 'category-' . uniqid() . '-' . $i]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            $paymentId = 'pay_' . uniqid();
            
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
            
            // Verify order is confirmed
            $order = Order::find($orderId);
            $this->assertEquals('confirmed', $order->status);
            $this->assertEquals($paymentId, $order->payment_id);
            $firstUpdatedAt = $order->updated_at;
            
            // Wait a moment to ensure timestamp would change if updated
            sleep(1);
            
            // Send same webhook again (duplicate)
            $response2 = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
                'X-Razorpay-Signature' => $validSignature,
            ]);
            
            $response2->assertStatus(200);
            $response2->assertJson([
                'success' => true,
                'message' => 'Webhook already processed',
            ]);
            
            // Verify order was not updated again
            $order->refresh();
            $this->assertEquals('confirmed', $order->status);
            $this->assertEquals($paymentId, $order->payment_id);
            $this->assertEquals($firstUpdatedAt->timestamp, $order->updated_at->timestamp);
        }
    }

    /**
     * Property 56: Invalid Webhook Signature Rejected
     * 
     * For any payment webhook with an invalid HMAC signature, the webhook
     * should be rejected and a security event should be logged.
     * 
     * **Validates: Requirements 6.13**
     */
    public function test_property_56_invalid_webhook_signature_rejected(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create an order
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create(['slug' => 'category-' . uniqid() . '-' . $i]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            
            // Create webhook payload
            $webhookPayload = [
                'event' => 'payment.captured',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_' . uniqid(),
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
            
            // Generate invalid signature (using wrong secret)
            $invalidSignature = hash_hmac('sha256', json_encode($webhookPayload), 'wrong_secret_' . $i);
            
            // Send webhook with invalid signature
            $response = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
                'X-Razorpay-Signature' => $invalidSignature,
            ]);
            
            // Webhook should be rejected
            $response->assertStatus(400);
            $response->assertJson([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ]);
            
            // Verify order status was NOT updated
            $order = Order::find($orderId);
            $this->assertEquals('pending', $order->status);
            $this->assertNull($order->payment_id);
        }
    }
}
