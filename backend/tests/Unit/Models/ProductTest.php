<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_fillable_attributes(): void
    {
        $product = new Product;

        $this->assertEquals([
            'category_id',
            'name',
            'slug',
            'description',
            'price',
            'stock',
            'images',
            'is_active',
        ], $product->getFillable());
    }

    public function test_product_casts_price_to_decimal(): void
    {
        $product = Product::factory()->create(['price' => 29.99]);

        $this->assertEquals('29.99', $product->price);
    }

    public function test_product_casts_stock_to_integer(): void
    {
        $product = Product::factory()->create(['stock' => '15']);

        $this->assertIsInt($product->stock);
        $this->assertEquals(15, $product->stock);
    }

    public function test_product_casts_images_to_array(): void
    {
        $images = ['products/img1.jpg', 'products/img2.jpg'];
        $product = Product::factory()->create(['images' => $images]);

        $this->assertIsArray($product->images);
        $this->assertEquals($images, $product->images);
    }

    public function test_product_casts_is_active_to_boolean(): void
    {
        $product = Product::factory()->create(['is_active' => 1]);

        $this->assertIsBool($product->is_active);
        $this->assertTrue($product->is_active);
    }

    public function test_saving_product_with_negative_stock_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative');

        Product::factory()->create(['stock' => -1]);
    }

    public function test_saving_product_with_zero_stock_succeeds(): void
    {
        $product = Product::factory()->create(['stock' => 0]);

        $this->assertEquals(0, $product->stock);
    }

    public function test_image_urls_returns_empty_array_when_no_images(): void
    {
        $product = Product::factory()->create(['images' => null]);

        $this->assertEquals([], $product->image_urls);
    }

    public function test_image_urls_returns_absolute_urls_unchanged(): void
    {
        $images = ['https://example.com/img1.jpg', 'http://example.com/img2.jpg'];
        $product = Product::factory()->create(['images' => $images]);

        $this->assertEquals($images, $product->image_urls);
    }

    public function test_product_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_has_many_cart_items(): void
    {
        $product = Product::factory()->create();

        $this->assertCount(0, $product->cartItems);
    }

    public function test_product_has_many_order_items(): void
    {
        $product = Product::factory()->create();

        $this->assertCount(0, $product->orderItems);
    }

    public function test_product_uses_soft_deletes(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }
}
