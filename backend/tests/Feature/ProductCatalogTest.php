<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_categories_with_caching()
    {
        // Create active and inactive categories
        $activeCategory = Category::factory()->create(['is_active' => true]);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Categories retrieved successfully',
            ])
            ->assertJsonCount(1, 'data.categories');

        // Verify caching
        $this->assertTrue(Cache::has('categories_tree'));
    }

    public function test_it_returns_paginated_products_with_filters()
    {
        $category = Category::factory()->create(['is_active' => true]);
        
        // Create products with different prices
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/products?min_price=150&max_price=250');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Products retrieved successfully',
            ])
            ->assertJsonCount(1, 'data.products')
            ->assertJsonStructure([
                'meta' => ['next_cursor', 'per_page'],
            ]);
    }

    public function test_it_returns_only_active_products_for_non_admin_users()
    {
        $category = Category::factory()->create(['is_active' => true]);
        
        Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $category->id, 'is_active' => false]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.products');
    }

    public function test_it_returns_product_detail_by_slug()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'test-product',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/products/test-product');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'slug' => 'test-product',
                    ],
                ],
            ]);
    }

    public function test_it_returns_404_for_inactive_product_for_non_admin()
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'inactive-product',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/products/inactive-product');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Product not found',
            ]);
    }

    public function test_admin_can_see_inactive_products()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['is_active' => true]);
        
        Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $category->id, 'is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.products');
    }

    public function test_it_eager_loads_category_relationship()
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);

        // Enable query log
        \DB::enableQueryLog();

        $this->getJson('/api/v1/products');

        $queries = \DB::getQueryLog();
        
        // Should have exactly 2 queries: 1 for products, 1 for categories
        $this->assertCount(2, $queries);
    }
}
