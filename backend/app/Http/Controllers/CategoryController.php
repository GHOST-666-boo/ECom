<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

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
        } catch (\Exception $e) {
            // Fall back to database if cache is unavailable (Requirement 11.7)
            $categories = Category::where('is_active', true)
                ->with('children')
                ->whereNull('parent_id')
                ->get()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ]);
    }
}
