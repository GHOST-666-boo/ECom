<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductFilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake R2 storage for testing
        Storage::fake('r2');
        
        // Create an admin user for testing
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test that the Product Filament resource is registered and accessible.
     */
    public function test_product_filament_resource_is_registered(): void
    {
        // Act as admin
        $this->actingAs($this->admin);
        
        // Access the Filament products list page
        $response = $this->get('/admin/products');
        
        // Should be accessible to admin
        $response->assertStatus(200);
    }

    /**
     * Test that non-admin users cannot access the Product Filament resource.
     */
    public function test_non_admin_cannot_access_product_resource(): void
    {
        // Create a regular customer user
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        
        // Act as customer
        $this->actingAs($customer);
        
        // Attempt to access the Filament products list page
        $response = $this->get('/admin/products');
        
        // Should be forbidden or redirected
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            'Non-admin users should not be able to access admin product resource'
        );
    }

    /**
     * Test that products can be created through the model with proper validation.
     */
    public function test_product_creation_with_all_fields(): void
    {
        $category = Category::factory()->create();
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Handmade Jewelry',
            'slug' => 'handmade-jewelry',
            'description' => 'Beautiful handmade jewelry from Indian artisans',
            'price' => 1299.99,
            'stock' => 50,
            'images' => ['products/image1.jpg', 'products/image2.jpg'],
            'is_active' => true,
        ]);
        
        $this->assertDatabaseHas('products', [
            'name' => 'Handmade Jewelry',
            'slug' => 'handmade-jewelry',
            'category_id' => $category->id,
            'price' => 1299.99,
            'stock' => 50,
            'is_active' => true,
        ]);
        
        $this->assertIsArray($product->images);
        $this->assertCount(2, $product->images);
    }

    /**
     * Test that products can be soft deleted.
     */
    public function test_product_soft_delete_functionality(): void
    {
        $category = Category::factory()->create();
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test description',
            'price' => 100.00,
            'stock' => 10,
            'is_active' => true,
        ]);
        
        $productId = $product->id;
        
        // Soft delete
        $product->delete();
        
        // Verify soft deleted
        $this->assertSoftDeleted('products', ['id' => $productId]);
        
        // Verify can be restored
        $product->restore();
        
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test that product relationships work correctly.
     */
    public function test_product_category_relationship(): void
    {
        $category = Category::factory()->create(['name' => 'Jewelry']);
        
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Silver Ring',
            'slug' => 'silver-ring',
            'description' => 'Handcrafted silver ring',
            'price' => 599.99,
            'stock' => 25,
            'is_active' => true,
        ]);
        
        // Test relationship
        $this->assertEquals('Jewelry', $product->category->name);
        $this->assertTrue($category->products->contains($product));
    }
}
