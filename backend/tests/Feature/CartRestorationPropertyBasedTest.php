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
 * These tests validate universal properties that should hold across all inputs
 * by running 100 iterations with randomized data.
 */
class CartRestorationPropertyBasedTest extends TestCase
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
     * Feature: artisan-kala-ecommerce, Property 50: Failed Payment Restores Cart
     * 
     * **Validates: Requirements 6.5**
     * 
     * Property: For any failed payment, the cart should be restored with the order items
     * (product_id and quantity).
     * 
     * This test runs 100 iterations with randomized:
     * - Number of products in order (1-10)
     * - Product stock levels (10-1000)
     * - Product prices (100-10000)
     * - Order item quantities (1-20)
     */
    public function test_property_50_failed_payment_restores_cart(): void
    {
        Notification::fake();
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create user and category with unique name
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create([
                'name' => 'Category ' . uniqid(),
                'slug' => 'category-' . uniqid(),
            ]);
            
            // Generate random number of products (1-10)
            $numProducts = rand(1, 10);
            $products = [];
            $orderItems = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $products[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => rand(10, 1000),
                    'is_active' => true,
                    'price' => rand(100, 10000),
                ]);
            }
            
            // Create cart and add items
            $cart = Cart::create(['user_id' => $user->id]);
            foreach ($products as $product) {
                // Ensure quantity doesn't exceed stock
                $quantity = rand(1, min(20, $product->stock));
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
                $orderItems[$product->id] = $quantity;
            }
            
            // Place order (this clears the cart)
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            
            // Verify cart is empty after order placement
            $this->assertEquals(0, CartItem::where('cart_id', $cart->id)->count(),
                "Iteration {$i}: Cart should be empty after order placement");
            
            // Simulate failed payment webhook
            $webhookPayload = [
                'event' => 'payment.failed',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_test_' . uniqid(),
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
            
            $webhookResponse = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
                'X-Razorpay-Signature' => $validSignature,
            ]);
            
            $webhookResponse->assertStatus(200);
            
            // Verify cart is restored with all order items
            $cartItems = CartItem::where('cart_id', $cart->id)->get();
            $this->assertEquals($numProducts, $cartItems->count(),
                "Iteration {$i}: Cart should have {$numProducts} items after restoration");
            
            // Verify each product and quantity matches
            foreach ($products as $product) {
                $cartItem = $cartItems->firstWhere('product_id', $product->id);
                $this->assertNotNull($cartItem,
                    "Iteration {$i}: Product {$product->id} should be in cart");
                $this->assertEquals($orderItems[$product->id], $cartItem->quantity,
                    "Iteration {$i}: Product {$product->id} quantity should match order item");
            }
            
            // Verify notification was sent
            Notification::assertSentTo($user, PaymentFailedNotification::class);
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property 51: Out-of-Stock Items Skipped on Cart Restoration
     * 
     * **Validates: Requirements 6.6**
     * 
     * Property: For any cart restoration after failed payment, if any product is out of stock,
     * that item should be skipped and the customer should be notified.
     * 
     * This test runs 100 iterations with randomized:
     * - Number of products in order (2-10)
     * - Number of out-of-stock products (1 to half of total products)
     * - Product stock levels (0 for out-of-stock, 10-1000 for in-stock)
     * - Product prices (100-10000)
     * - Order item quantities (1-20)
     */
    public function test_property_51_out_of_stock_items_skipped_on_cart_restoration(): void
    {
        Notification::fake();
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create user and category with unique name
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
            $category = Category::factory()->create([
                'name' => 'Category ' . uniqid(),
                'slug' => 'category-' . uniqid(),
            ]);
            
            // Generate random number of products (2-10, need at least 2 for mixed stock scenario)
            $numProducts = rand(2, 10);
            $products = [];
            $orderItems = [];
            
            // Determine how many products will be out of stock (at least 1, at most half)
            $numOutOfStock = rand(1, max(1, intval($numProducts / 2)));
            $outOfStockIndices = array_rand(range(0, $numProducts - 1), $numOutOfStock);
            if (!is_array($outOfStockIndices)) {
                $outOfStockIndices = [$outOfStockIndices];
            }
            
            $expectedInStockCount = $numProducts - $numOutOfStock;
            $expectedOutOfStockProducts = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $isOutOfStock = in_array($j, $outOfStockIndices);
                $stock = $isOutOfStock ? rand(5, 20) : rand(10, 1000);
                
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => $stock,
                    'is_active' => true,
                    'price' => rand(100, 10000),
                ]);
                
                $products[] = $product;
                
                if ($isOutOfStock) {
                    $expectedOutOfStockProducts[] = $product;
                }
            }
            
            // Create cart and add items
            $cart = Cart::create(['user_id' => $user->id]);
            foreach ($products as $product) {
                // Ensure quantity doesn't exceed stock
                $quantity = rand(1, min(20, $product->stock));
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
                $orderItems[$product->id] = $quantity;
            }
            
            // Place order (this clears the cart)
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            $orderId = $response->json('data.order.id');
            
            // Make selected products out of stock
            foreach ($expectedOutOfStockProducts as $product) {
                $product->update(['stock' => 0]);
            }
            
            // Simulate failed payment webhook
            $webhookPayload = [
                'event' => 'payment.failed',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_test_' . uniqid(),
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
            
            $webhookResponse = $this->postJson('/api/v1/webhooks/razorpay', $webhookPayload, [
                'X-Razorpay-Signature' => $validSignature,
            ]);
            
            $webhookResponse->assertStatus(200);
            
            // Verify only in-stock products are restored
            $cartItems = CartItem::where('cart_id', $cart->id)->get();
            $this->assertEquals($expectedInStockCount, $cartItems->count(),
                "Iteration {$i}: Cart should have {$expectedInStockCount} in-stock items (out of {$numProducts} total)");
            
            // Verify out-of-stock products are NOT in cart
            foreach ($expectedOutOfStockProducts as $outOfStockProduct) {
                $cartItem = $cartItems->firstWhere('product_id', $outOfStockProduct->id);
                $this->assertNull($cartItem,
                    "Iteration {$i}: Out-of-stock product {$outOfStockProduct->id} should NOT be in cart");
            }
            
            // Verify in-stock products ARE in cart with correct quantities
            foreach ($products as $product) {
                if (!in_array($product, $expectedOutOfStockProducts)) {
                    $cartItem = $cartItems->firstWhere('product_id', $product->id);
                    $this->assertNotNull($cartItem,
                        "Iteration {$i}: In-stock product {$product->id} should be in cart");
                    $this->assertEquals($orderItems[$product->id], $cartItem->quantity,
                        "Iteration {$i}: In-stock product {$product->id} quantity should match order item");
                }
            }
            
            // Verify notification was sent with skipped items
            Notification::assertSentTo(
                $user,
                PaymentFailedNotification::class,
                function ($notification) use ($numOutOfStock, $i) {
                    $skippedCount = count($notification->skippedItems);
                    $this->assertEquals($numOutOfStock, $skippedCount,
                        "Iteration {$i}: Should have {$numOutOfStock} skipped items, got {$skippedCount}");
                    return $skippedCount === $numOutOfStock;
                }
            );
        }
    }
}
