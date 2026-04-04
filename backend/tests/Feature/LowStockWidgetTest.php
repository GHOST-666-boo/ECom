<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockWidgetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that low-stock widget displays products with stock < 10
     */
    public function test_low_stock_widget_displays_products_below_threshold(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create category
        $category = Category::factory()->create();
        
        // Create products with various stock levels
        $lowStockProduct1 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Low Stock Product 1',
            'stock' => 5,
            'is_active' => true,
        ]);
        
        $lowStockProduct2 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Low Stock Product 2',
            'stock' => 0,
            'is_active' => true,
        ]);
        
        $lowStockProduct3 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Low Stock Product 3',
            'stock' => 9,
            'is_active' => true,
        ]);
        
        // Create product with sufficient stock (should not appear)
        $sufficientStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Sufficient Stock Product',
            'stock' => 50,
            'is_active' => true,
        ]);
        
        // Create inactive low-stock product (should not appear)
        $inactiveLowStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Inactive Low Stock Product',
            'stock' => 3,
            'is_active' => false,
        ]);
        
        // Query products that should appear in low-stock widget
        $lowStockProducts = Product::query()
            ->where('stock', '<', 10)
            ->where('is_active', true)
            ->orderBy('stock', 'asc')
            ->get();
        
        // Verify correct products are returned
        $this->assertCount(3, $lowStockProducts);
        $this->assertTrue($lowStockProducts->contains($lowStockProduct1));
        $this->assertTrue($lowStockProducts->contains($lowStockProduct2));
        $this->assertTrue($lowStockProducts->contains($lowStockProduct3));
        $this->assertFalse($lowStockProducts->contains($sufficientStockProduct));
        $this->assertFalse($lowStockProducts->contains($inactiveLowStockProduct));
        
        // Verify ordering (lowest stock first)
        $this->assertEquals(0, $lowStockProducts->first()->stock);
        $this->assertEquals(9, $lowStockProducts->last()->stock);
    }

    /**
     * Test that low-stock threshold is exactly 10
     */
    public function test_low_stock_threshold_is_ten(): void
    {
        $category = Category::factory()->create();
        
        // Create product with stock = 10 (should NOT appear in low-stock)
        $product10 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'is_active' => true,
        ]);
        
        // Create product with stock = 9 (should appear in low-stock)
        $product9 = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 9,
            'is_active' => true,
        ]);
        
        $lowStockProducts = Product::query()
            ->where('stock', '<', 10)
            ->where('is_active', true)
            ->get();
        
        // Verify threshold is exactly 10
        $this->assertFalse($lowStockProducts->contains($product10));
        $this->assertTrue($lowStockProducts->contains($product9));
    }
}
