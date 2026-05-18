<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Task 62: Checkout and Order Flow Integration Tests
 * 
 * Tests the complete checkout flow including:
 * - Address management
 * - Cart operations
 * - Order placement (COD and Razorpay)
 * - Order history
 * - Order details
 * - Order cancellation
 */
class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);
        
        // Create test category and product
        $category = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000,
            'stock' => 10,
        ]);
        
        // Create test address
        $this->address = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);
    }

    public function test_user_can_view_addresses_for_checkout()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/addresses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'addresses' => [
                        '*' => ['id', 'name', 'line1', 'city', 'state', 'pincode', 'is_default']
                    ]
                ]
            ]);
    }

    public function test_user_can_place_cod_order()
    {
        // Add product to cart
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'order' => [
                    'id', 'order_number', 'status', 'total', 'payment_method'
                ]
            ]);

        $this->assertEquals('confirmed', $response->json('order.status'));
        $this->assertEquals('cod', $response->json('order.payment_method'));
    }

    public function test_user_can_view_order_history()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'orders',
                'meta' => ['next_cursor', 'per_page']
            ]);
    }

    public function test_user_can_view_order_details()
    {
        // Create an order first
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $orderResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $orderId = $orderResponse->json('order.id');

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$orderId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'order' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                    'address_snapshot',
                    'items' => [
                        '*' => ['id', 'quantity', 'price', 'product']
                    ]
                ]
            ]);
    }

    public function test_user_can_cancel_pending_order()
    {
        // Create a pending order
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        // Place Razorpay order (stays pending)
        $orderResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'razorpay',
            ]);

        $orderId = $orderResponse->json('order.id');

        // Cancel the order
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$orderId}/cancel");

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
        ]);
    }

    public function test_user_cannot_cancel_confirmed_order()
    {
        // Create a confirmed order (COD)
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $orderResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $orderId = $orderResponse->json('order.id');

        // Try to cancel
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$orderId}/cancel");

        $response->assertStatus(422);
    }

    public function test_order_validates_insufficient_stock()
    {
        // Set product stock to 1
        $this->product->update(['stock' => 1]);

        // Try to order 5 items
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422);
    }

    public function test_order_requires_address()
    {
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_order_clears_cart_after_placement()
    {
        $cart = Cart::factory()->create(['user_id' => $this->user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $this->assertDatabaseCount('cart_items', 0);
    }
}
