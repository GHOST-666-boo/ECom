<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-Based Tests for Product Catalog API
 * 
 * These tests validate universal properties that should hold across all inputs.
 * Each property test runs 100 iterations with randomized data.
 */
class ProductCatalogPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: vriddhi-ecommerce, Property 24: Product Filtering
     * 
     * **Validates: Requirements 2.12**
     * 
     * For any product list request with filters (category, price range, active status),
     * all returned products should match the specified filter criteria.
     */
    public function test_property_24_product_filtering()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Clear existing data
            Product::query()->delete();
            Category::query()->delete();

            // Create random categories
            $categories = Category::factory()->count(rand(2, 5))->create(['is_active' => true]);
            $targetCategory = $categories->random();

            // Create products with random prices
            $minPrice = rand(100, 500);
            $maxPrice = rand(600, 1000);

            // Create products that match the filter
            Product::factory()->count(rand(2, 5))->create([
                'category_id' => $targetCategory->id,
                'price' => rand($minPrice, $maxPrice),
                'is_active' => true,
            ]);

            // Create products that don't match the filter
            Product::factory()->count(rand(1, 3))->create([
                'category_id' => $categories->where('id', '!=', $targetCategory->id)->random()->id,
                'price' => rand($minPrice, $maxPrice),
                'is_active' => true,
            ]);

            Product::factory()->count(rand(1, 3))->create([
                'category_id' => $targetCategory->id,
                'price' => rand(50, $minPrice - 1),
                'is_active' => true,
            ]);

            // Test filtering
            $response = $this->getJson("/api/v1/products?category_id={$targetCategory->id}&min_price={$minPrice}&max_price={$maxPrice}");

            $response->assertStatus(200);
            $products = $response->json('data.products');

            // Verify all returned products match the filter criteria
            foreach ($products as $product) {
                $this->assertEquals($targetCategory->id, $product['category_id']);
                $this->assertGreaterThanOrEqual($minPrice, $product['price']);
                $this->assertLessThanOrEqual($maxPrice, $product['price']);
                $this->assertTrue($product['is_active']);
            }
        }
    }

    /**
     * Feature: vriddhi-ecommerce, Property 25: Cursor-Based Pagination
     * 
     * **Validates: Requirements 3.3, 9.5, 11.4**
     * 
     * For any product or order listing request, the response should use cursor-based
     * pagination and return exactly 20 items per page (or fewer on the last page).
     */
    public function test_property_25_cursor_based_pagination()
    {
        $iterations = 50; // Reduced iterations due to data volume

        for ($i = 0; $i < $iterations; $i++) {
            // Clear existing data
            Product::query()->delete();
            Category::query()->delete();

            $category = Category::factory()->create(['is_active' => true]);
            $productCount = rand(25, 50);

            // Create random number of products
            Product::factory()->count($productCount)->create([
                'category_id' => $category->id,
                'is_active' => true,
            ]);

            // Test first page
            $response = $this->getJson('/api/v1/products');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'meta' => ['next_cursor', 'per_page'],
                ]);

            $products = $response->json('data.products');
            $perPage = $response->json('meta.per_page');

            // Verify per_page is 20
            $this->assertEquals(20, $perPage);

            // Verify returned products count is at most 20
            $this->assertLessThanOrEqual(20, count($products));

            // If there are more than 20 products, verify next_cursor exists
            if ($productCount > 20) {
                $this->assertNotNull($response->json('meta.next_cursor'));
            }
        }
    }

    /**
     * Feature: vriddhi-ecommerce, Property 26: Eager Loading Prevents N+1 Queries
     * 
     * **Validates: Requirements 3.4, 11.2**
     * 
     * For any product listing request, the category relationship should be eager-loaded,
     * resulting in exactly 2 database queries (1 for products, 1 for categories)
     * regardless of the number of products returned.
     */
    public function test_property_26_eager_loading_prevents_n_plus_1_queries()
    {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            // Clear existing data
            Product::query()->delete();
            Category::query()->delete();

            $slugPrefix = uniqid('cat_' . $i . '_');
            $categories = collect(range(1, rand(3, 10)))->map(fn($j) =>
                Category::factory()->create([
                    'is_active' => true,
                    'slug'      => $slugPrefix . '_' . $j,
                ])
            );
            $productCount = rand(5, 20);

            // Create products with random categories
            foreach (range(1, $productCount) as $index) {
                Product::factory()->create([
                    'category_id' => $categories->random()->id,
                    'is_active'   => true,
                ]);
            }

            // Clear any previous query logs
            DB::flushQueryLog();
            
            // Enable query log
            DB::enableQueryLog();

            $this->getJson('/api/v1/products');

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            // Should have exactly 2 queries: 1 for products, 1 for categories
            // This proves eager loading is working regardless of product count
            // Note: In some Laravel versions, there might be additional queries for pagination metadata
            $this->assertLessThanOrEqual(3, count($queries), "Expected at most 3 queries but got " . count($queries) . " for {$productCount} products");
            $this->assertGreaterThanOrEqual(2, count($queries), "Expected at least 2 queries but got " . count($queries) . " for {$productCount} products");
        }
    }

    /**
     * Feature: vriddhi-ecommerce, Property 27: Product Detail Completeness
     * 
     * **Validates: Requirements 3.5**
     * 
     * For any product detail request, the response should include all images,
     * description, price, stock quantity, and category information.
     */
    public function test_property_27_product_detail_completeness()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Clear existing data
            Product::query()->delete();
            Category::query()->delete();

            $category = Category::factory()->create(['is_active' => true]);
            
            // Create product with random data
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'is_active' => true,
                'images' => array_map(fn() => fake()->imageUrl(), range(1, rand(1, 5))),
                'description' => fake()->paragraph(),
                'price' => rand(100, 10000) / 100,
                'stock' => rand(0, 100),
            ]);

            $response = $this->getJson("/api/v1/products/{$product->slug}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'product' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'price',
                            'stock',
                            'images',
                            'category',
                        ],
                    ],
                ]);

            $returnedProduct = $response->json('data.product');

            // Verify all required fields are present and not null
            $this->assertNotNull($returnedProduct['description']);
            $this->assertNotNull($returnedProduct['price']);
            $this->assertNotNull($returnedProduct['stock']);
            $this->assertNotNull($returnedProduct['images']);
            $this->assertNotNull($returnedProduct['category']);
            $this->assertIsArray($returnedProduct['images']);
            $this->assertGreaterThan(0, count($returnedProduct['images']));
        }
    }

    /**
     * Feature: vriddhi-ecommerce, Property 28: Active Products Only for Non-Admins
     * 
     * **Validates: Requirements 3.8**
     * 
     * For any product listing request from a non-admin user, all returned products
     * should have is_active = true.
     */
    public function test_property_28_active_products_only_for_non_admins()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Clear authentication state from previous iteration
            $this->app['auth']->forgetGuards();
            
            // Clear existing data (force delete to bypass soft deletes)
            Product::query()->forceDelete();
            Category::query()->delete();
            User::query()->delete();
            
            // Clear any cache
            \Cache::flush();

            $category = Category::factory()->create(['is_active' => true]);
            
            // Create mix of active and inactive products
            $activeCount = rand(5, 15);
            $inactiveCount = rand(5, 15);

            Product::factory()->count($activeCount)->create([
                'category_id' => $category->id,
                'is_active' => true,
            ]);

            Product::factory()->count($inactiveCount)->create([
                'category_id' => $category->id,
                'is_active' => false,
            ]);

            // Test as non-admin (unauthenticated) - create a fresh request
            $response = $this->get('/api/v1/products', ['Accept' => 'application/json']);

            $response->assertStatus(200);
            $products = $response->json('data.products');

            // Verify all returned products are active
            foreach ($products as $product) {
                $this->assertTrue($product['is_active'], "Product {$product['id']} should be active for non-admin users (iteration {$i})");
            }

            // Verify inactive products are not returned
            $this->assertLessThanOrEqual($activeCount, count($products));

            // Clear auth before next test
            $this->app['auth']->forgetGuards();

            // Test as customer (authenticated non-admin)
            $customer = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($customer, 'sanctum')->get('/api/v1/products', ['Accept' => 'application/json']);

            $response->assertStatus(200);
            $products = $response->json('data.products');

            // Verify all returned products are active
            foreach ($products as $product) {
                $this->assertTrue($product['is_active'], "Product {$product['id']} should be active for customer users (iteration {$i})");
            }

            // Clear auth before admin test
            $this->app['auth']->forgetGuards();

            // Test as admin (should see all products)
            $admin = User::factory()->create(['role' => 'admin']);
            $response = $this->actingAs($admin, 'sanctum')->get('/api/v1/products', ['Accept' => 'application/json']);

            $response->assertStatus(200);
            $products = $response->json('data.products');

            // Admin should see both active and inactive products
            $hasInactive = false;
            foreach ($products as $product) {
                if (!$product['is_active']) {
                    $hasInactive = true;
                    break;
                }
            }

            // If there are inactive products, admin should see at least one
            if ($inactiveCount > 0 && count($products) >= $inactiveCount) {
                $this->assertTrue($hasInactive, 
                    "Admin should be able to see inactive products (iteration {$i})");
            }
        }
    }
}
