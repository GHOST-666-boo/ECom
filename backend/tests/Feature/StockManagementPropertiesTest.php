<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 93: Zero Stock Prevents Add to Cart
     * 
     * For any product with stock quantity = 0, attempting to add it to the cart
     * should fail with a validation error.
     * 
     * **Validates: Requirements 15.7**
     */
    public function test_property_93_zero_stock_prevents_add_to_cart(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create product with zero stock
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => 0,
                'is_active' => true,
            ]);
            
            $quantity = rand(1, 5);
            
            // Attempt to add product to cart
            $response = $this->actingAs($user)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            
            // Should return HTTP 422 with validation error
            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
                'message' => 'Product is out of stock',
            ]);
            $response->assertJsonStructure([
                'errors' => ['product_id'],
            ]);
            
            // Verify no cart item was created
            $this->assertDatabaseMissing('cart_items', [
                'product_id' => $product->id,
            ]);
        }
    }

    /**
     * Property 94: Non-Negative Stock Validation
     * 
     * For any admin product stock update with a negative value, the request
     * should fail with a validation error.
     * 
     * **Validates: Requirements 15.8**
     */
    public function test_property_94_non_negative_stock_validation(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'stock' => rand(10, 100),
                'is_active' => true,
            ]);
            
            $originalStock = $product->stock;
            
            // Generate negative stock value
            $negativeStock = rand(-100, -1);
            
            // Attempt to update product with negative stock
            $product->stock = $negativeStock;
            
            // The model validation should prevent saving
            $exceptionThrown = false;
            try {
                $product->save();
            } catch (\InvalidArgumentException $e) {
                $exceptionThrown = true;
                $this->assertStringContainsString('Stock quantity cannot be negative', $e->getMessage());
            }
            
            // Verify exception was thrown
            $this->assertTrue($exceptionThrown, 
                "Expected InvalidArgumentException for negative stock value {$negativeStock}");
            
            // Verify stock was not updated in database
            $product->refresh();
            $this->assertEquals($originalStock, $product->stock,
                "Stock should remain unchanged at {$originalStock}, found: {$product->stock}");
            $this->assertGreaterThanOrEqual(0, $product->stock,
                "Product stock should not be negative, found: {$product->stock}");
        }
    }
}
