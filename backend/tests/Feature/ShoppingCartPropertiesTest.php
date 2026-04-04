<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShoppingCartPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 29: One Cart Per User
     * 
     * For any authenticated user, there should be exactly one cart associated
     * with that user in the database.
     * 
     * **Validates: Requirements 4.1**
     */
    public function test_property_29_one_cart_per_user(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            // Add item to cart multiple times
            $addCount = rand(2, 5);
            for ($j = 0; $j < $addCount; $j++) {
                $this->actingAs($user)
                    ->postJson('/api/v1/cart/items', [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ]);
            }
            
            // Verify exactly one cart exists for the user
            $cartCount = Cart::where('user_id', $user->id)->count();
            $this->assertEquals(1, $cartCount, "User should have exactly one cart, found {$cartCount}");
            
            // Verify the cart belongs to the user
            $cart = Cart::where('user_id', $user->id)->first();
            $this->assertNotNull($cart);
            $this->assertEquals($user->id, $cart->user_id);
        }
    }

    /**
     * Property 30: Add to Cart Creates Item
     * 
     * For any product added to a cart for the first time, a new cart_item record
     * should be created with the specified product_id and quantity.
     * 
     * **Validates: Requirements 4.2**
     */
    public function test_property_30_add_to_cart_creates_item(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $quantity = rand(1, 10);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            
            $response->assertStatus(201);
            
            // Verify cart_item was created
            $this->assertDatabaseHas('cart_items', [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
            
            // Verify cart_item has correct product_id and quantity
            $cart = Cart::where('user_id', $user->id)->first();
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            $this->assertNotNull($cartItem);
            $this->assertEquals($product->id, $cartItem->product_id);
            $this->assertEquals($quantity, $cartItem->quantity);
        }
    }

    /**
     * Property 31: Add to Cart Increments Existing Item
     * 
     * For any product already in the cart, adding it again should increment
     * the existing cart_item quantity rather than creating a duplicate cart_item.
     * 
     * **Validates: Requirements 4.3**
     */
    public function test_property_31_add_to_cart_increments_existing_item(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $firstQuantity = rand(1, 5);
            $secondQuantity = rand(1, 5);
            
            // Add product to cart first time
            $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => $firstQuantity,
                ]);
            
            // Add same product to cart second time
            $response = $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => $secondQuantity,
                ]);
            
            $response->assertStatus(201);
            
            // Verify only one cart_item exists for this product
            $cart = Cart::where('user_id', $user->id)->first();
            $cartItemCount = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->count();
            
            $this->assertEquals(1, $cartItemCount, "Should have exactly one cart_item, found {$cartItemCount}");
            
            // Verify quantity was incremented
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            $expectedQuantity = $firstQuantity + $secondQuantity;
            $this->assertEquals($expectedQuantity, $cartItem->quantity);
        }
    }

    /**
     * Property 32: Minimum Cart Item Quantity
     * 
     * For any cart_item, the quantity should always be at least 1.
     * 
     * **Validates: Requirements 4.4**
     */
    public function test_property_32_minimum_cart_item_quantity(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            // Add product to cart
            $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ]);
            
            $cart = Cart::where('user_id', $user->id)->first();
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            // Try to update quantity to 0 or negative
            $invalidQuantity = rand(-10, 0);
            
            $response = $this->actingAs($user)
                ->putJson("/api/v1/cart/items/{$cartItem->id}", [
                    'quantity' => $invalidQuantity,
                ]);
            
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['quantity']);
            
            // Verify quantity was not updated
            $cartItem->refresh();
            $this->assertGreaterThanOrEqual(1, $cartItem->quantity);
        }
    }

    /**
     * Property 33: Cart Quantity Validates Against Stock
     * 
     * For any cart_item quantity update, if the new quantity exceeds the product's
     * available stock, the update should fail with a validation error.
     * 
     * **Validates: Requirements 4.5**
     */
    public function test_property_33_cart_quantity_validates_against_stock(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $stock = rand(5, 20);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => $stock,
                'is_active' => true,
            ]);
            
            // Add product to cart
            $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]);
            
            $cart = Cart::where('user_id', $user->id)->first();
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            // Try to update quantity to exceed stock
            $excessQuantity = $stock + rand(1, 10);
            
            $response = $this->actingAs($user)
                ->putJson("/api/v1/cart/items/{$cartItem->id}", [
                    'quantity' => $excessQuantity,
                ]);
            
            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
                'message' => 'Insufficient stock',
            ]);
            
            // Verify quantity was not updated
            $cartItem->refresh();
            $this->assertLessThanOrEqual($stock, $cartItem->quantity);
        }
    }

    /**
     * Property 34: Remove Cart Item Deletes Record
     * 
     * For any cart_item removal, the cart_item record should no longer exist
     * in the database.
     * 
     * **Validates: Requirements 4.6**
     */
    public function test_property_34_remove_cart_item_deletes_record(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            // Add product to cart
            $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => rand(1, 10),
                ]);
            
            $cart = Cart::where('user_id', $user->id)->first();
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            $cartItemId = $cartItem->id;
            
            // Remove cart item
            $response = $this->actingAs($user)
                ->deleteJson("/api/v1/cart/items/{$cartItemId}");
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Cart item removed successfully',
            ]);
            
            // Verify cart_item no longer exists
            $this->assertDatabaseMissing('cart_items', [
                'id' => $cartItemId,
            ]);
            
            $deletedCartItem = CartItem::find($cartItemId);
            $this->assertNull($deletedCartItem);
        }
    }

    /**
     * Property 35: Cart Retrieval Includes Product Details
     * 
     * For any cart retrieval request, the response should include product details
     * (name, price, images) and current prices for all cart_items.
     * 
     * **Validates: Requirements 4.8**
     */
    public function test_property_35_cart_retrieval_includes_product_details(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Add multiple products to cart
            $productCount = rand(1, 5);
            $products = [];
            
            for ($j = 0; $j < $productCount; $j++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => 100,
                    'is_active' => true,
                ]);
                $products[] = $product;
                
                $this->actingAs($user)
                    ->postJson('/api/v1/cart/items', [
                        'product_id' => $product->id,
                        'quantity' => rand(1, 5),
                    ]);
            }
            
            // Retrieve cart
            $response = $this->actingAs($user)
                ->getJson('/api/v1/cart');
            
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cart' => [
                        'id',
                        'user_id',
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
            
            $cartData = $response->json('data.cart');
            
            // Verify each cart item includes product details
            foreach ($cartData['items'] as $item) {
                $this->assertNotNull($item['product']);
                $this->assertArrayHasKey('name', $item['product']);
                $this->assertArrayHasKey('price', $item['product']);
                $this->assertArrayHasKey('images', $item['product']);
                $this->assertArrayHasKey('category', $item['product']);
                
                // Verify product details match database
                $product = Product::find($item['product_id']);
                $this->assertEquals($product->name, $item['product']['name']);
                $this->assertEquals($product->price, $item['product']['price']);
                $this->assertEquals($product->images, $item['product']['images']);
            }
        }
    }

    /**
     * Property 36: Cart Operations Require Authentication
     * 
     * For any cart operation (add, update, remove, retrieve) without a valid
     * authentication token, the response should be HTTP 401 Unauthorized.
     * 
     * **Validates: Requirements 4.9**
     */
    public function test_property_36_cart_operations_require_authentication(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $category = Category::factory()->create([
                'slug' => 'category-auth-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            // Test GET /cart without authentication
            $response = $this->getJson('/api/v1/cart');
            $response->assertStatus(401);
            
            // Test POST /cart/items without authentication
            $response = $this->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
            ]);
            $response->assertStatus(401);
            
            // Create a cart item for update/delete tests
            $user = User::factory()->create();
            $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]);
            
            $cart = Cart::where('user_id', $user->id)->first();
            $cartItem = CartItem::where('cart_id', $cart->id)->first();
            
            // Clear authentication for subsequent tests
            $this->app['auth']->forgetGuards();
            
            // Test PUT /cart/items/{id} without authentication
            $response = $this->putJson("/api/v1/cart/items/{$cartItem->id}", [
                'quantity' => rand(1, 5),
            ]);
            $response->assertStatus(401);
            
            // Test DELETE /cart/items/{id} without authentication
            $response = $this->deleteJson("/api/v1/cart/items/{$cartItem->id}");
            $response->assertStatus(401);
        }
    }
}
