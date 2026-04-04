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
use Illuminate\Support\Facades\Config;
use Razorpay\Api\Api;
use Tests\TestCase;

class RazorpayOrderCreationPropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Razorpay API for testing
        $this->mockRazorpayApi();
    }

    /**
     * Mock Razorpay API to avoid actual API calls during testing
     */
    protected function mockRazorpayApi(): void
    {
        // Create a mock Razorpay order object
        $mockOrder = new \stdClass();
        $mockOrder->id = 'order_' . fake()->uuid();
        $mockOrder->amount = 0;
        $mockOrder->currency = 'INR';

        // Mock the Razorpay API
        $mockApi = $this->createMock(Api::class);
        $mockOrderApi = $this->createMock(\Razorpay\Api\Order::class);
        
        $mockOrderApi->method('create')
            ->willReturnCallback(function ($data) use ($mockOrder) {
                $mockOrder->amount = $data['amount'];
                return $mockOrder;
            });

        $mockApi->order = $mockOrderApi;

        // Bind the mock to the container
        $this->app->bind(Api::class, function () use ($mockApi) {
            return $mockApi;
        });
    }

    /**
     * Feature: artisan-kala-ecommerce, Property: Razorpay Order Creation Requires Valid Order ID
     * 
     * For any Razorpay order creation request, the order_id must exist and belong to the authenticated user.
     * 
     * **Validates: Requirements 5.9, 6.1**
     */
    public function test_razorpay_order_creation_requires_valid_order_id(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a verified user
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            // Create address and cart with items
            $address = Address::factory()->create(['user_id' => $user->id]);
            // Use unique category name and slug to avoid collisions
            $uniqueId = uniqid();
            $category = Category::factory()->create([
                'name' => 'Category ' . $uniqueId,
                'slug' => 'category-' . $uniqueId,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'price' => fake()->randomFloat(2, 10, 1000),
                'stock' => fake()->numberBetween(5, 100),
            ]);

            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => fake()->numberBetween(1, 3),
            ]);

            // Place a Razorpay order
            $orderResponse = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);

            $orderId = $orderResponse->json('data.order.id');

            // Test 1: Valid order_id should succeed
            $response = $this->actingAs($user)
                ->postJson('/api/v1/payments/razorpay/create', [
                    'order_id' => $orderId,
                ]);

            $this->assertEquals(200, $response->status(), 
                "Iteration {$i}: Valid order_id should return 200");
            $this->assertTrue($response->json('success'), 
                "Iteration {$i}: Valid order_id should have success=true");

            // Test 2: Non-existent order_id should fail
            $invalidOrderId = 999999;
            $response = $this->actingAs($user)
                ->postJson('/api/v1/payments/razorpay/create', [
                    'order_id' => $invalidOrderId,
                ]);

            $this->assertEquals(422, $response->status(), 
                "Iteration {$i}: Non-existent order_id should return 422");
            // Laravel validation returns errors in a different format when validation fails
            $this->assertArrayHasKey('errors', $response->json(), 
                "Iteration {$i}: Non-existent order_id should have errors key");

            // Test 3: Order belonging to another user should fail
            $otherUser = User::factory()->create(['email_verified_at' => now()]);
            $response = $this->actingAs($otherUser)
                ->postJson('/api/v1/payments/razorpay/create', [
                    'order_id' => $orderId,
                ]);

            $this->assertEquals(422, $response->status(), 
                "Iteration {$i}: Order belonging to another user should return 422");
            $this->assertFalse($response->json('success'), 
                "Iteration {$i}: Order belonging to another user should have success=false");
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property: Razorpay Order Creation Only for Razorpay Payment Method
     * 
     * For any Razorpay order creation request, the order must have payment_method = 'razorpay'.
     * COD orders should not be able to create Razorpay orders.
     * 
     * **Validates: Requirements 5.9, 6.1**
     */
    public function test_razorpay_order_creation_only_for_razorpay_payment_method(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a verified user
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            // Create address and cart with items
            $address = Address::factory()->create(['user_id' => $user->id]);
            // Use unique category name and slug to avoid collisions
            $uniqueId = uniqid();
            $category = Category::factory()->create([
                'name' => 'Category ' . $uniqueId,
                'slug' => 'category-' . $uniqueId,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'price' => fake()->randomFloat(2, 10, 1000),
                'stock' => fake()->numberBetween(5, 100),
            ]);

            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => fake()->numberBetween(1, 3),
            ]);

            // Place a COD order
            $codOrderResponse = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'cod',
                ]);

            $codOrderId = $codOrderResponse->json('data.order.id');

            // Attempt to create Razorpay order for COD order (should fail)
            $response = $this->actingAs($user)
                ->postJson('/api/v1/payments/razorpay/create', [
                    'order_id' => $codOrderId,
                ]);

            $this->assertEquals(422, $response->status(), 
                "Iteration {$i}: COD order should not allow Razorpay order creation");
            $this->assertFalse($response->json('success'), 
                "Iteration {$i}: COD order should have success=false");
            $this->assertStringContainsString('not use Razorpay', $response->json('errors.payment_method.0'), 
                "Iteration {$i}: Error message should mention payment method");
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property: Razorpay Order Amount Conversion to Paise
     * 
     * For any Razorpay order creation, the amount should be converted from rupees to paise (multiply by 100).
     * The returned amount should be in paise (integer).
     * 
     * **Validates: Requirements 5.9, 6.1**
     */
    public function test_razorpay_order_amount_converted_to_paise(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a verified user
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            // Create address and cart with items
            $address = Address::factory()->create(['user_id' => $user->id]);
            // Use unique category name and slug to avoid collisions
            $uniqueId = uniqid();
            $category = Category::factory()->create([
                'name' => 'Category ' . $uniqueId,
                'slug' => 'category-' . $uniqueId,
            ]);
            
            // Generate random price
            $productPrice = fake()->randomFloat(2, 10, 1000);
            $quantity = fake()->numberBetween(1, 5);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'price' => $productPrice,
                'stock' => fake()->numberBetween(10, 100),
            ]);

            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);

            // Place a Razorpay order
            $orderResponse = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);

            $orderId = $orderResponse->json('data.order.id');
            $orderTotal = $orderResponse->json('data.order.total');

            // Create Razorpay order
            $response = $this->actingAs($user)
                ->postJson('/api/v1/payments/razorpay/create', [
                    'order_id' => $orderId,
                ]);

            $this->assertEquals(200, $response->status(), 
                "Iteration {$i}: Should return 200");

            // Verify amount is in paise
            $amountInPaise = $response->json('data.amount');
            $expectedAmountInPaise = (int) ($orderTotal * 100);

            $this->assertEquals($expectedAmountInPaise, $amountInPaise, 
                "Iteration {$i}: Amount should be converted to paise (order total: {$orderTotal}, expected: {$expectedAmountInPaise}, got: {$amountInPaise})");
            
            $this->assertIsInt($amountInPaise, 
                "Iteration {$i}: Amount should be an integer");
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property: Razorpay Order Returns Order ID
     * 
     * For any successful Razorpay order creation, the response should include a razorpay_order_id.
     * 
     * **Validates: Requirements 5.9, 6.1**
     */
    public function test_razorpay_order_returns_order_id(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a verified user
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            // Create address and cart with items
            $address = Address::factory()->create(['user_id' => $user->id]);
            // Use unique category name and slug to avoid collisions
            $uniqueId = uniqid();
            $category = Category::factory()->create([
                'name' => 'Category ' . $uniqueId,
                'slug' => 'category-' . $uniqueId,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'price' => fake()->randomFloat(2, 10, 1000),
                'stock' => fake()->numberBetween(5, 100),
            ]);

            $cart = Cart::create(['user_id' => $user->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => fake()->numberBetween(1, 3),
            ]);

            // Place a Razorpay order
            $orderResponse = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);

            $orderId = $orderResponse->json('data.order.id');

            // Create Razorpay order
            $response = $this->actingAs($user)
                ->postJson('/api/v1/payments/razorpay/create', [
                    'order_id' => $orderId,
                ]);

            $this->assertEquals(200, $response->status(), 
                "Iteration {$i}: Should return 200");
            $this->assertTrue($response->json('success'), 
                "Iteration {$i}: Should have success=true");

            // Verify razorpay_order_id is present
            $razorpayOrderId = $response->json('data.razorpay_order_id');
            $this->assertNotNull($razorpayOrderId, 
                "Iteration {$i}: razorpay_order_id should not be null");
            $this->assertIsString($razorpayOrderId, 
                "Iteration {$i}: razorpay_order_id should be a string");
            $this->assertNotEmpty($razorpayOrderId, 
                "Iteration {$i}: razorpay_order_id should not be empty");

            // Verify currency is INR
            $this->assertEquals('INR', $response->json('data.currency'), 
                "Iteration {$i}: Currency should be INR");

            // Verify order_number is included
            $this->assertNotNull($response->json('data.order_number'), 
                "Iteration {$i}: order_number should be included");
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property: Razorpay Order Creation Requires Authentication
     * 
     * For any Razorpay order creation request without authentication, the request should fail with 401.
     * 
     * **Validates: Requirements 5.9, 6.1, 9.8**
     */
    public function test_razorpay_order_creation_requires_authentication(): void
    {
        // Simple test: just verify unauthenticated request fails
        $response = $this->postJson('/api/v1/payments/razorpay/create', [
            'order_id' => 1,
        ]);

        $this->assertEquals(401, $response->status(), 
            "Unauthenticated request should return 401");
    }
}
