<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Get all active categories with images.
     * Cache category tree in Redis for 1 hour.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $categories = Cache::remember('categories_tree', 3600, function () {
                return Category::where('is_active', true)
                    ->with('children')
                    ->whereNull('parent_id')
                    ->get()
                    ->toArray();
            });
        } catch (\Throwable $e) {
            // Log the cache failure for observability, then fall back to database (Requirement 11.7)
            Log::warning('Cache unavailable for categories_tree, falling back to database', [
                'error' => $e->getMessage(),
            ]);

            $categories = Category::where('is_active', true)
                ->with('children')
                ->whereNull('parent_id')
                ->get()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'categories' => $categories,  // Direct field instead of nested 'data'
        ]);
    }
}
