<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get paginated product listing with filters.
     * Supports filters: category_id, min_price, max_price, is_active
     * Uses cursor-based pagination with 20 products per page.
     * Eager loads category relationship to prevent N+1 queries.
     * Returns only active products for non-admin users.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|integer|min:1',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $query = Product::with('category');

        // Filter by category_id
        if ($request->has('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        // Filter by min_price
        if ($request->has('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }

        // Filter by max_price
        if ($request->has('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Filter by is_active (only for admin users)
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        if ($request->has('is_active') && $isAdmin) {
            $query->where('is_active', (bool) $request->is_active);
        } elseif (!$isAdmin) {
            // Non-admin users only see active products
            $query->where('is_active', true);
        }

        // Cursor-based pagination with 20 products per page
        $products = $query->cursorPaginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'products' => $products->items(),  // Direct field
            'meta' => [
                'next_cursor' => $products->nextCursor()?->encode(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }

    /**
     * Get product detail by slug.
     * Returns product with all images, description, price, stock, and category.
     * Returns HTTP 404 if product not found or inactive (for non-admin users).
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $slug)
    {
        $query = Product::with('category')->where('slug', $slug);

        // Non-admin users can only see active products
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        if (!$isAdmin) {
            $query->where('is_active', true);
        }

        $product = $query->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'product' => $product,  // Direct field
        ]);
    }
}
