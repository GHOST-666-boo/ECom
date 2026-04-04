<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create a test category and product
        $category = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_cart(): void
    {
        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_retrieve_empty_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cart retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'cart' => [
                        'id',
                        'user_id',
                        'items',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(0, $response->json('data.cart.items'));
    }

    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product added to cart successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'cart_item' => [
                        'id',
                        'product_id',
                        'quantity',
                        'product',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_existing_product_increments_quantity(): void
    {
        // First add
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        // Second add
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 3,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 15, // More than stock (10)
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient stock',
            ]);
    }

    public function test_authenticated_user_can_update_cart_item_quantity(): void
    {
        // Create cart and cart item
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/cart/items/{$cartItem->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cart item updated successfully',
            ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_update_quantity_below_one(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/cart/items/{$cartItem->id}", [
                'quantity' => 0,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_update_quantity_beyond_stock(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/cart/items/{$cartItem->id}", [
                'quantity' => 15, // More than stock (10)
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient stock',
            ]);
    }

    public function test_authenticated_user_can_remove_cart_item(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/cart/items/{$cartItem->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cart item removed successfully',
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_user_cannot_access_another_users_cart_item(): void
    {
        $otherUser = User::factory()->create();
        $otherCart = Cart::create(['user_id' => $otherUser->id]);
        $otherCartItem = CartItem::create([
            'cart_id' => $otherCart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        // Try to update another user's cart item
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/cart/items/{$otherCartItem->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(404);

        // Try to delete another user's cart item
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/cart/items/{$otherCartItem->id}");

        $response->assertStatus(404);
    }

    public function test_cart_retrieval_includes_product_details(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'cart' => [
                        'items' => [
                            '*' => [
                                'id',
                                'product_id',
                                'quantity',
                                'product' => [
                                    'id',
                                    'name',
                                    'slug',
                                    'price',
                                    'stock',
                                    'images',
                                    'is_active',
                                    'category',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertEquals($this->product->id, $response->json('data.cart.items.0.product.id'));
        $this->assertEquals($this->product->name, $response->json('data.cart.items.0.product.name'));
    }
}
