<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheFallbackPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 83: Cache Fallback on Failure
     * 
     * For any API request that uses caching, if the cache is unavailable,
     * the API should fall back to the database without error.
     * 
     * **Validates: Requirements 11.7**
     */
    public function test_property_83_cache_fallback_on_failure()
    {
        // Create test data
        $category = Category::factory()->create(['is_active' => true]);

        // Mock Cache to throw an exception (simulating Redis unavailable)
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        // The request should still succeed by falling back to database
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Categories retrieved successfully',
            ])
            ->assertJsonCount(1, 'data.categories');
    }

    /**
     * Test that cache fallback returns the same data as cached version
     */
    public function test_cache_fallback_returns_correct_data()
    {
        // Create test categories
        $parentCategory = Category::factory()->create([
            'name' => 'Parent Category',
            'is_active' => true,
            'parent_id' => null,
        ]);
        
        $childCategory = Category::factory()->create([
            'name' => 'Child Category',
            'is_active' => true,
            'parent_id' => $parentCategory->id,
        ]);

        // First request - should cache the data
        Cache::flush();
        $cachedResponse = $this->getJson('/api/v1/categories');
        $cachedData = $cachedResponse->json('data.categories');

        // Mock Cache to throw an exception
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        // Second request - should fall back to database
        $fallbackResponse = $this->getJson('/api/v1/categories');
        $fallbackData = $fallbackResponse->json('data.categories');

        // Both responses should have the same structure and data
        $this->assertEquals(count($cachedData), count($fallbackData));
        $this->assertEquals($cachedData[0]['name'], $fallbackData[0]['name']);
    }
}

