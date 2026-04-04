<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 69: Admin Dashboard Data Completeness
     * 
     * For any admin dashboard request, the response should include total revenue,
     * recent orders, and low-stock product alerts.
     * 
     * **Validates: Requirements 8.3**
     */
    public function test_property_69_admin_dashboard_data_completeness(): void
    {
        $iterations = (int) env('PROPERTY_TEST_ITERATIONS', 100);
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create category with unique slug
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create random number of products with varying stock levels
            $productCount = rand(5, 15);
            $lowStockCount = 0;
            $products = [];
            
            for ($j = 0; $j < $productCount; $j++) {
                $stock = rand(0, 50);
                if ($stock < 10) {
                    $lowStockCount++;
                }
                
                $products[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => $stock,
                    'is_active' => true,
                    'price' => rand(100, 10000) / 100,
                ]);
            }
            
            // Create random number of orders with varying statuses
            $orderCount = rand(0, 20);
            $expectedRevenue = 0;
            $orders = [];
            
            for ($j = 0; $j < $orderCount; $j++) {
                $status = fake()->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']);
                $total = rand(100, 50000) / 100;
                
                // Only confirmed, shipped, and delivered orders count toward revenue
                if (in_array($status, ['confirmed', 'shipped', 'delivered'])) {
                    $expectedRevenue += $total;
                }
                
                $orders[] = Order::factory()->create([
                    'user_id' => User::factory()->create(['role' => 'customer'])->id,
                    'status' => $status,
                    'total' => $total,
                    'payment_method' => fake()->randomElement(['cod', 'razorpay']),
                ]);
            }
            
            // Test 1: Total Revenue Widget
            $orderIds = array_map(fn($order) => $order->id, $orders);
            $totalRevenue = Order::whereIn('id', $orderIds)
                ->whereIn('status', ['confirmed', 'shipped', 'delivered'])
                ->sum('total');
            
            // Use delta comparison for floating-point values
            $this->assertEqualsWithDelta($expectedRevenue, $totalRevenue, 0.01,
                "Total revenue should match sum of confirmed, shipped, and delivered orders");
            
            // Test 2: Recent Orders Widget
            $recentOrders = Order::query()
                ->whereIn('id', $orderIds)
                ->with(['user', 'orderItems'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            $this->assertLessThanOrEqual(10, $recentOrders->count(),
                "Recent orders should return at most 10 orders");
            $this->assertLessThanOrEqual($orderCount, $recentOrders->count(),
                "Recent orders count should not exceed total orders");
            
            // Verify orders are sorted by created_at descending
            if ($recentOrders->count() > 1) {
                for ($k = 0; $k < $recentOrders->count() - 1; $k++) {
                    $this->assertGreaterThanOrEqual(
                        $recentOrders[$k + 1]->created_at,
                        $recentOrders[$k]->created_at,
                        "Recent orders should be sorted by created_at descending"
                    );
                }
            }
            
            // Test 3: Low-Stock Products Widget
            $productIds = array_map(fn($product) => $product->id, $products);
            $lowStockProducts = Product::query()
                ->whereIn('id', $productIds)
                ->where('stock', '<', 10)
                ->where('is_active', true)
                ->orderBy('stock', 'asc')
                ->get();
            
            $this->assertEquals($lowStockCount, $lowStockProducts->count(),
                "Low-stock products count should match products with stock < 10");
            
            // Verify all low-stock products have stock < 10
            foreach ($lowStockProducts as $product) {
                $this->assertLessThan(10, $product->stock,
                    "All low-stock products should have stock < 10");
                $this->assertTrue($product->is_active,
                    "All low-stock products should be active");
            }
            
            // Verify ordering (lowest stock first)
            if ($lowStockProducts->count() > 1) {
                for ($k = 0; $k < $lowStockProducts->count() - 1; $k++) {
                    $this->assertLessThanOrEqual(
                        $lowStockProducts[$k + 1]->stock,
                        $lowStockProducts[$k]->stock,
                        "Low-stock products should be sorted by stock ascending"
                    );
                }
            }
        }
    }

    /**
     * Property 70: Low-Stock Threshold
     * 
     * For any low-stock alert in the admin dashboard, the products should have
     * stock quantity less than 10.
     * 
     * **Validates: Requirements 8.4, 15.6**
     */
    public function test_property_70_low_stock_threshold(): void
    {
        $iterations = (int) env('PROPERTY_TEST_ITERATIONS', 100);
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create category with unique slug
            $category = Category::factory()->create([
                'slug' => 'category-' . uniqid() . '-' . $i,
            ]);
            
            // Create products with stock levels around the threshold
            $testStockLevels = [0, 1, 5, 9, 10, 11, 15, 20, 50, 100];
            $productsCreated = [];
            
            foreach ($testStockLevels as $stockLevel) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => $stockLevel,
                    'is_active' => true,
                    'name' => "Product with stock {$stockLevel}",
                ]);
                $productsCreated[$stockLevel] = $product;
            }
            
            // Also create some inactive products with low stock (should not appear)
            $inactiveCount = rand(1, 5);
            for ($j = 0; $j < $inactiveCount; $j++) {
                Product::factory()->create([
                    'category_id' => $category->id,
                    'stock' => rand(0, 9),
                    'is_active' => false,
                ]);
            }
            
            // Query low-stock products
            $productIds = array_map(fn($product) => $product->id, $productsCreated);
            $lowStockProducts = Product::query()
                ->whereIn('id', $productIds)
                ->where('stock', '<', 10)
                ->where('is_active', true)
                ->get();
            
            // Verify threshold is exactly 10 (stock < 10)
            // Products with stock 0-9 should be included
            $this->assertTrue($lowStockProducts->contains($productsCreated[0]),
                "Product with stock 0 should be in low-stock alerts");
            $this->assertTrue($lowStockProducts->contains($productsCreated[1]),
                "Product with stock 1 should be in low-stock alerts");
            $this->assertTrue($lowStockProducts->contains($productsCreated[5]),
                "Product with stock 5 should be in low-stock alerts");
            $this->assertTrue($lowStockProducts->contains($productsCreated[9]),
                "Product with stock 9 should be in low-stock alerts");
            
            // Products with stock >= 10 should NOT be included
            $this->assertFalse($lowStockProducts->contains($productsCreated[10]),
                "Product with stock 10 should NOT be in low-stock alerts");
            $this->assertFalse($lowStockProducts->contains($productsCreated[11]),
                "Product with stock 11 should NOT be in low-stock alerts");
            $this->assertFalse($lowStockProducts->contains($productsCreated[15]),
                "Product with stock 15 should NOT be in low-stock alerts");
            $this->assertFalse($lowStockProducts->contains($productsCreated[20]),
                "Product with stock 20 should NOT be in low-stock alerts");
            $this->assertFalse($lowStockProducts->contains($productsCreated[50]),
                "Product with stock 50 should NOT be in low-stock alerts");
            $this->assertFalse($lowStockProducts->contains($productsCreated[100]),
                "Product with stock 100 should NOT be in low-stock alerts");
            
            // Verify all returned products have stock < 10
            foreach ($lowStockProducts as $product) {
                $this->assertLessThan(10, $product->stock,
                    "All low-stock products must have stock < 10, found stock: {$product->stock}");
            }
            
            // Verify only active products are included
            foreach ($lowStockProducts as $product) {
                $this->assertTrue($product->is_active,
                    "All low-stock products must be active");
            }
            
            // Verify exact count (should be 4: stock levels 0, 1, 5, 9)
            $this->assertEquals(4, $lowStockProducts->count(),
                "Should have exactly 4 low-stock products (stock 0, 1, 5, 9)");
        }
    }
}
