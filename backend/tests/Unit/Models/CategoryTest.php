<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_fillable_attributes(): void
    {
        $category = new Category;

        $this->assertEquals([
            'name',
            'slug',
            'image',
            'parent_id',
            'is_active',
        ], $category->getFillable());
    }

    public function test_category_casts_is_active_to_boolean(): void
    {
        $category = Category::factory()->create(['is_active' => 1]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_image_url_returns_null_when_no_image(): void
    {
        $category = Category::factory()->create(['image' => null]);

        $this->assertNull($category->image_url);
    }

    public function test_image_url_returns_absolute_http_url_unchanged(): void
    {
        $url = 'https://example.com/category.jpg';
        $category = Category::factory()->create(['image' => $url]);

        $this->assertEquals($url, $category->image_url);
    }

    public function test_image_url_returns_http_url_unchanged(): void
    {
        $url = 'http://example.com/category.jpg';
        $category = Category::factory()->create(['image' => $url]);

        $this->assertEquals($url, $category->image_url);
    }

    public function test_category_belongs_to_parent(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_category_has_many_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->count(3)->create(['parent_id' => $parent->id]);

        $this->assertCount(3, $parent->children);
    }

    public function test_category_has_many_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->products);
    }

    public function test_category_parent_is_null_for_root_category(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);

        $this->assertNull($category->parent);
    }
}
