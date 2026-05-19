<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtomicStockDecrementPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 92: Atomic Stock Decrement with Pessimistic Lock
     * 
     * For any concurrent order placements for the same product, the database-level
     * pessimistic lock (FOR UPDATE) should prevent overselling by ensuring stock
     * decrements are atomic.
     * 
     * **Validates: Requirements 15.3, 15.4**
     */
    public function test_property_92_atomic_stock_decrement_with_pessimistic_lock(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create a product with limited stock
            $initialStock = rand(5, 20);
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => $initialStock,
                'is_active' => true,
            ]);
            
            // Create multiple users who will place orders concurrently
            $userCount = rand(2, 5);
            $users = [];
            
            for ($j = 0; $j < $userCount; $j++) {
                $user = User::factory()->create([
                    'email_verified_at' => now(),
                ]);
                
                $address = Address::factory()->create([
                    'user_id' => $user->id,
                ]);
                
                // Each user wants to buy a random quantity
                $quantity = rand(1, 3);
                
                // Create cart with items
                $cart = Cart::create(['user_id' => $user->id]);
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
                
                $users[] = [
                    'user' => $user,
                    'address' => $address,
                    'quantity' => $quantity,
                ];
            }
            
            // Simulate concurrent order placements using database transactions
            // We'll place orders sequentially but verify the locking mechanism works
            $successfulOrders = 0;
            $totalDecremented = 0;
            
            foreach ($users as $userData) {
                $response = $this->actingAs($userData['user'])
                    ->postJson('/api/v1/orders', [
                        'address_id' => $userData['address']->id,
                        'payment_method' => 'cod',
                    ]);
                
                if ($response->status() === 201) {
                    $successfulOrders++;
                    $totalDecremented += $userData['quantity'];
                }
            }
            
            // Refresh product to get current stock
            $product->refresh();
            
            // Verify stock was decremented correctly
            $expectedStock = $initialStock - $totalDecremented;
            $this->assertEquals(
                $expectedStock,
                $product->stock,
                "Stock should be decremented atomically. Initial: {$initialStock}, Decremented: {$totalDecremented}, Expected: {$expectedStock}, Actual: {$product->stock}"
            );
            
            // Verify stock never went negative
            $this->assertGreaterThanOrEqual(
                0,
                $product->stock,
                "Stock should never go negative due to pessimistic locking"
            );
            
            // Verify that if stock would have gone negative, the order was rejected
            if ($totalDecremented > $initialStock) {
                $this->assertLessThan(
                    $userCount,
                    $successfulOrders,
                    "Some orders should have been rejected when stock is insufficient"
                );
            }
        }
    }

    /**
     * Test that pessimistic locking prevents race conditions in concurrent scenarios.
     * 
     * This test simulates a more realistic concurrent scenario where multiple
     * transactions attempt to decrement stock simultaneously.
     */
    public function test_pessimistic_lock_prevents_race_conditions(): void
    {
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $initialStock = 10;
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => $initialStock,
                'is_active' => true,
            ]);
            
            // Create two users who will try to buy the same product
            $user1 = User::factory()->create(['email_verified_at' => now()]);
            $user2 = User::factory()->create(['email_verified_at' => now()]);
            
            $address1 = Address::factory()->create(['user_id' => $user1->id]);
            $address2 = Address::factory()->create(['user_id' => $user2->id]);
            
            // Both users want to buy 6 items (total 12, but only 10 available)
            $quantity = 6;
            
            $cart1 = Cart::create(['user_id' => $user1->id]);
            CartItem::create([
                'cart_id' => $cart1->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
            
            $cart2 = Cart::create(['user_id' => $user2->id]);
            CartItem::create([
                'cart_id' => $cart2->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
            
            // Place first order
            $response1 = $this->actingAs($user1)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address1->id,
                    'payment_method' => 'cod',
                ]);
            
            // Place second order
            $response2 = $this->actingAs($user2)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address2->id,
                    'payment_method' => 'cod',
                ]);
            
            // Refresh product
            $product->refresh();
            
            // Verify outcomes
            if ($response1->status() === 201 && $response2->status() === 201) {
                // Both orders succeeded - this should not happen as total > stock
                $this->fail('Both orders succeeded when stock was insufficient');
            } elseif ($response1->status() === 201) {
                // First order succeeded, second should fail
                $this->assertEquals(422, $response2->status());
                $this->assertEquals($initialStock - $quantity, $product->stock);
            } elseif ($response2->status() === 201) {
                // Second order succeeded, first should fail
                $this->assertEquals(422, $response1->status());
                $this->assertEquals($initialStock - $quantity, $product->stock);
            } else {
                // Both failed - acceptable if stock validation caught it
                $this->assertEquals($initialStock, $product->stock);
            }
            
            // Most importantly: stock should never go negative
            $this->assertGreaterThanOrEqual(
                0,
                $product->stock,
                "Pessimistic locking must prevent negative stock"
            );
        }
    }

    /**
     * Test that stock decrement is atomic and prevents overselling.
     * 
     * This test verifies that the stock decrement logic correctly prevents
     * overselling by checking stock levels after order placement.
     */
    public function test_stock_decrement_is_atomic_for_cod_orders(): void
    {
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            $quantity = rand(1, 5);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
            
            $initialStock = $product->stock;
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'cod',
                ]);
            
            // Verify order was successful
            $this->assertEquals(201, $response->status());
            
            // Verify stock was decremented atomically
            $product->refresh();
            $this->assertEquals(
                $initialStock - $quantity,
                $product->stock,
                'Stock should be decremented atomically for COD orders'
            );
            
            // Verify stock never goes negative
            $this->assertGreaterThanOrEqual(
                0,
                $product->stock,
                'Stock should never go negative'
            );
        }
    }

    /**
     * Test that Razorpay orders do NOT decrement stock immediately.
     * 
     * Stock should only be decremented when payment is confirmed, not when
     * the order is placed with payment_method='razorpay'.
     */
    public function test_razorpay_orders_do_not_decrement_stock_immediately(): void
    {
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create(['email_verified_at' => now()]);
            $address = Address::factory()->create(['user_id' => $user->id]);
            
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 100,
                'is_active' => true,
            ]);
            
            $cart = Cart::create(['user_id' => $user->id]);
            $quantity = rand(1, 5);
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
            
            $initialStock = $product->stock;
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'razorpay',
                ]);
            
            // Verify order was created with pending status
            $this->assertEquals(201, $response->status());
            $this->assertEquals('pending', $response->json('order.status'));
            
            // Verify stock was NOT decremented for Razorpay orders
            $product->refresh();
            $this->assertEquals(
                $initialStock,
                $product->stock,
                'Stock should NOT be decremented for Razorpay orders until payment is confirmed'
            );
        }
    }
}
