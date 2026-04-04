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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Address $address;
    protected Product $product;
    protected Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a verified test user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create a test address
        $this->address = Address::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create a test category and product
        $category = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Create cart with items
        $this->cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_unauthenticated_user_cannot_place_order(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'address_id' => $this->address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(401);
    }

    public function test_unverified_user_cannot_place_order(): void
    {
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $address = Address::factory()->create([
            'user_id' => $unverifiedUser->id,
        ]);

        $cart = Cart::create(['user_id' => $unverifiedUser->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($unverifiedUser)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Email verification required',
            ]);
    }

    public function test_user_can_place_cod_order_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order placed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'status',
                        'payment_method',
                        'total',
                        'address_snapshot',
                        'items',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'payment_method' => 'cod',
            'status' => 'confirmed', // COD orders are immediately confirmed
        ]);

        // Verify order items were created
        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Verify cart was cleared
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $this->cart->id,
        ]);
    }

    public function test_order_number_format_is_correct(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201);

        $orderNumber = $response->json('data.order.order_number');
        $year = date('Y');

        // Verify format: ORD-YYYYNNNNN
        $this->assertMatchesRegularExpression("/^ORD-{$year}\d{5}$/", $orderNumber);
    }

    public function test_order_total_is_calculated_correctly(): void
    {
        // Add another product to cart
        $product2 = Product::factory()->create([
            'category_id' => $this->product->category_id,
            'price' => 50.00,
            'stock' => 5,
            'is_active' => true,
        ]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201);

        // Expected total: (2 * 100) + (3 * 50) = 350
        $this->assertEquals(350.00, $response->json('data.order.total'));
    }

    public function test_address_snapshot_is_stored_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201);

        $addressSnapshot = $response->json('data.order.address_snapshot');

        $this->assertEquals($this->address->name, $addressSnapshot['name']);
        $this->assertEquals($this->address->line1, $addressSnapshot['line1']);
        $this->assertEquals($this->address->line2, $addressSnapshot['line2']);
        $this->assertEquals($this->address->city, $addressSnapshot['city']);
        $this->assertEquals($this->address->state, $addressSnapshot['state']);
        $this->assertEquals($this->address->pincode, $addressSnapshot['pincode']);
    }

    public function test_razorpay_order_has_pending_status(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'razorpay',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'order' => [
                        'status' => 'pending',
                        'payment_method' => 'razorpay',
                    ],
                ],
            ]);
    }

    public function test_cannot_place_order_with_empty_cart(): void
    {
        // Clear cart
        $this->cart->cartItems()->delete();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cart is empty',
            ]);
    }

    public function test_cannot_place_order_with_insufficient_stock(): void
    {
        // Update product stock to less than cart quantity
        $this->product->update(['stock' => 1]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Some products are out of stock',
            ]);

        // Verify order was not created
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_place_order_with_address_belonging_to_another_user(): void
    {
        $otherUser = User::factory()->create();
        $otherAddress = Address::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $otherAddress->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Address not found or does not belong to you',
            ]);
    }

    public function test_address_id_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_payment_method_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_payment_method_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_order_items_store_price_snapshot(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201);

        $orderId = $response->json('data.order.id');
        $orderItem = OrderItem::where('order_id', $orderId)->first();

        // Verify price snapshot matches current product price
        $this->assertEquals($this->product->price, $orderItem->price);

        // Change product price
        $this->product->update(['price' => 200.00]);

        // Verify order item still has old price
        $orderItem->refresh();
        $this->assertEquals(100.00, $orderItem->price);
    }

    public function test_order_number_sequence_increments_correctly(): void
    {
        // Place first order
        $response1 = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $orderNumber1 = $response1->json('data.order.order_number');

        // Add items back to cart for second order
        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        // Place second order
        $response2 = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $orderNumber2 = $response2->json('data.order.order_number');

        // Extract sequence numbers
        $seq1 = (int) substr($orderNumber1, -5);
        $seq2 = (int) substr($orderNumber2, -5);

        // Verify second order number is incremented
        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_cod_order_decrements_stock_atomically(): void
    {
        // Verify initial stock
        $this->assertEquals(10, $this->product->stock);

        // Place COD order (should decrement stock)
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201);

        // Refresh product and verify stock was decremented
        $this->product->refresh();
        $this->assertEquals(8, $this->product->stock); // 10 - 2 = 8
    }

    public function test_razorpay_order_does_not_decrement_stock_until_confirmed(): void
    {
        // Verify initial stock
        $this->assertEquals(10, $this->product->stock);

        // Place Razorpay order (should NOT decrement stock yet)
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'razorpay',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'order' => [
                        'status' => 'pending',
                    ],
                ],
            ]);

        // Refresh product and verify stock was NOT decremented
        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock); // Stock unchanged
    }

    // Order History and Detail Tests (Task 25.1)

    public function test_user_can_view_order_history(): void
    {
        // Create multiple orders for the user with different timestamps
        $order1 = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-202400001',
            'status' => 'confirmed',
            'total' => 100.00,
            'created_at' => now()->subDays(2),
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-202400002',
            'status' => 'shipped',
            'total' => 200.00,
            'created_at' => now()->subDays(1),
        ]);

        // Create order for another user (should not appear)
        $otherUser = User::factory()->create();
        Order::factory()->create([
            'user_id' => $otherUser->id,
            'order_number' => 'ORD-202400003',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Orders retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'orders' => [
                        '*' => [
                            'id',
                            'order_number',
                            'status',
                            'total',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
                'meta' => [
                    'next_cursor',
                    'per_page',
                ],
            ]);

        // Verify only user's orders are returned
        $orders = $response->json('data.orders');
        $this->assertCount(2, $orders);
        $this->assertEquals('ORD-202400002', $orders[0]['order_number']); // Most recent first
        $this->assertEquals('ORD-202400001', $orders[1]['order_number']);
    }

    public function test_order_history_uses_cursor_pagination(): void
    {
        // Create 25 orders (more than 20 per page)
        for ($i = 1; $i <= 25; $i++) {
            Order::factory()->create([
                'user_id' => $this->user->id,
                'order_number' => sprintf('ORD-2024%05d', $i),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);

        // Verify pagination metadata
        $this->assertEquals(20, $response->json('meta.per_page'));
        $this->assertNotNull($response->json('meta.next_cursor'));

        // Verify exactly 20 orders returned
        $this->assertCount(20, $response->json('data.orders'));
    }

    public function test_unauthenticated_user_cannot_view_order_history(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }

    public function test_user_can_view_order_details(): void
    {
        // Create order with items
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-202400001',
            'status' => 'confirmed',
            'payment_method' => 'cod',
            'total' => 200.00,
            'address_snapshot' => [
                'name' => 'John Doe',
                'line1' => '123 Main St',
                'line2' => 'Apt 4',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
            ],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100.00, // Price snapshot
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order retrieved successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => 'ORD-202400001',
                        'status' => 'confirmed',
                        'payment_method' => 'cod',
                        'total' => 200.00,
                        'address_snapshot' => [
                            'name' => 'John Doe',
                            'line1' => '123 Main St',
                            'line2' => 'Apt 4',
                            'city' => 'Mumbai',
                            'state' => 'Maharashtra',
                            'pincode' => '400001',
                        ],
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'items' => [
                            '*' => [
                                'id',
                                'product_id',
                                'quantity',
                                'price',
                                'product' => [
                                    'id',
                                    'name',
                                    'slug',
                                    'images',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_order_detail_uses_price_snapshot_not_current_price(): void
    {
        // Create order with price snapshot
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total' => 200.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100.00, // Original price snapshot
        ]);

        // Change product price
        $this->product->update(['price' => 150.00]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200);

        // Verify order item uses price snapshot (100.00), not current price (150.00)
        $items = $response->json('data.order.items');
        $this->assertEquals(100.00, $items[0]['price']);
    }

    public function test_order_detail_uses_address_snapshot_not_current_address(): void
    {
        // Create order with address snapshot
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'address_snapshot' => [
                'name' => 'John Doe',
                'line1' => '123 Old Street',
                'line2' => null,
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
            ],
        ]);

        // Update the user's address
        $this->address->update([
            'line1' => '456 New Street',
            'city' => 'Delhi',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200);

        // Verify order uses address snapshot, not current address
        $addressSnapshot = $response->json('data.order.address_snapshot');
        $this->assertEquals('123 Old Street', $addressSnapshot['line1']);
        $this->assertEquals('Mumbai', $addressSnapshot['city']);
    }

    public function test_user_cannot_view_order_belonging_to_another_user(): void
    {
        // Create order for another user
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }

    public function test_order_detail_returns_404_for_nonexistent_order(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }

    public function test_unauthenticated_user_cannot_view_order_details(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(401);
    }

    public function test_order_detail_includes_product_information(): void
    {
        // Create order with items
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200);

        // Verify product information is included
        $items = $response->json('data.order.items');
        $this->assertNotNull($items[0]['product']);
        $this->assertEquals($this->product->id, $items[0]['product']['id']);
        $this->assertEquals($this->product->name, $items[0]['product']['name']);
        $this->assertEquals($this->product->slug, $items[0]['product']['slug']);
        $this->assertNotNull($items[0]['product']['images']);
    }

    // Order Cancellation Tests (Task 30.2)

    public function test_customer_can_cancel_pending_order(): void
    {
        // Create a pending order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'payment_method' => 'razorpay',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Set initial stock
        $this->product->update(['stock' => 5]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => 'cancelled',
                    ],
                ],
            ]);

        // Verify order status was updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        // Verify stock was restored (5 + 2 = 7)
        $this->product->refresh();
        $this->assertEquals(7, $this->product->stock);
    }

    public function test_customer_cannot_cancel_confirmed_order(): void
    {
        // Create a confirmed order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be cancelled',
                'errors' => [
                    'status' => ["Orders with status 'confirmed' cannot be cancelled. Only pending orders can be cancelled."],
                ],
            ]);

        // Verify order status was not changed
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_customer_cannot_cancel_shipped_order(): void
    {
        // Create a shipped order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'shipped',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be cancelled',
            ]);

        // Verify order status was not changed
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
        ]);
    }

    public function test_customer_cannot_cancel_delivered_order(): void
    {
        // Create a delivered order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be cancelled',
            ]);

        // Verify order status was not changed
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);
    }

    public function test_customer_cannot_cancel_already_cancelled_order(): void
    {
        // Create a cancelled order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be cancelled',
            ]);
    }

    public function test_customer_cannot_cancel_order_belonging_to_another_user(): void
    {
        // Create order for another user
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);

        // Verify order status was not changed
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_cancel_order_returns_404_for_nonexistent_order(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/orders/99999/cancel');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found',
            ]);
    }

    public function test_unauthenticated_user_cannot_cancel_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(401);
    }

    public function test_cancel_order_restores_stock_for_multiple_items(): void
    {
        // Create another product
        $product2 = Product::factory()->create([
            'category_id' => $this->product->category_id,
            'price' => 50.00,
            'stock' => 10,
        ]);

        // Create a pending order with multiple items
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 5,
            'price' => 50.00,
        ]);

        // Set initial stock
        $this->product->update(['stock' => 7]);
        $product2->update(['stock' => 15]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200);

        // Verify stock was restored for both products
        $this->product->refresh();
        $product2->refresh();
        $this->assertEquals(10, $this->product->stock); // 7 + 3 = 10
        $this->assertEquals(20, $product2->stock); // 15 + 5 = 20
    }

    public function test_cancel_order_handles_deleted_product_gracefully(): void
    {
        // Create a pending order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Soft delete the product
        $this->product->delete();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        // Should still succeed even if product is deleted
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
            ]);

        // Verify order status was updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }
}


