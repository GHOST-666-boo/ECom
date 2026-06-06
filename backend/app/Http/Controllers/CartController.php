<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Get user cart with eager-loaded product details and current prices.
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create cart for user
        $cart = Cart::with(['cartItems.product.category'])
            ->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully',
            'data' => [
                'cart' => [
                    'id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'items' => $cart->cartItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'slug' => $item->product->slug,
                                'price' => $item->product->price,
                                'stock' => $item->product->stock,
                                'images' => $item->product->images,
                                'image_urls' => $item->product->image_urls,
                                'is_active' => $item->product->is_active,
                                'category' => $item->product->category,
                            ] : null,
                        ];
                    }),
                    'created_at' => $cart->created_at,
                    'updated_at' => $cart->updated_at,
                ],
            ],
        ]);
    }

    /**
     * Add product to cart (create cart if not exists, create or increment cart_item).
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        // Check if product exists and is active
        $product = Product::find($validated['product_id']);
        
        if (!$product || !$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or inactive',
                'errors' => [
                    'product_id' => ['Product not found or inactive'],
                ],
            ], 422);
        }

        // Prevent adding products with zero stock (Requirement 15.7)
        if ($product->stock === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Product is out of stock',
                'errors' => [
                    'product_id' => ['This product is currently out of stock and cannot be added to cart'],
                ],
            ], 422);
        }

        // Check if product has sufficient stock
        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => [
                    'quantity' => ['Requested quantity exceeds available stock'],
                ],
            ], 422);
        }

        // Get or create cart for user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Check if product already in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($cartItem) {
            // Increment existing cart item quantity
            $newQuantity = $cartItem->quantity + $validated['quantity'];
            
            // Validate new quantity against stock
            if ($newQuantity > $product->stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock',
                    'errors' => [
                        'quantity' => ['Total quantity would exceed available stock'],
                    ],
                ], 422);
            }
            
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        // Load product relationship
        $cartItem->load('product.category');

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'data' => [
                'cart_item' => [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'product' => $cartItem->product ? [
                        'id' => $cartItem->product->id,
                        'name' => $cartItem->product->name,
                        'slug' => $cartItem->product->slug,
                        'price' => $cartItem->product->price,
                        'stock' => $cartItem->product->stock,
                        'images' => $cartItem->product->images,
                        'image_urls' => $cartItem->product->image_urls,
                        'is_active' => $cartItem->product->is_active,
                        'category' => $cartItem->product->category,
                    ] : null,
                ],
            ],
        ], 201);
    }

    /**
     * Update cart item quantity (validate against stock, enforce min quantity 1).
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request, int $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        // Find cart item and verify it belongs to user's cart
        $cartItem = CartItem::with('product.category')
            ->whereHas('cart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Validate quantity against product stock
        $product = $cartItem->product;
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'errors' => [
                    'product_id' => ['Product not found'],
                ],
            ], 422);
        }

        if ($validated['quantity'] > $product->stock) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => [
                    'quantity' => ['Requested quantity exceeds available stock'],
                ],
            ], 422);
        }

        // Update cart item quantity
        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'data' => [
                'cart_item' => [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'product' => $cartItem->product ? [
                        'id' => $cartItem->product->id,
                        'name' => $cartItem->product->name,
                        'slug' => $cartItem->product->slug,
                        'price' => $cartItem->product->price,
                        'stock' => $cartItem->product->stock,
                        'images' => $cartItem->product->images,
                        'image_urls' => $cartItem->product->image_urls,
                        'is_active' => $cartItem->product->is_active,
                        'category' => $cartItem->product->category,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Remove cart item.
     * Requires authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem(Request $request, int $id)
    {
        $user = $request->user();

        // Find cart item and verify it belongs to user's cart
        $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($id);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Delete cart item
        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item removed successfully',
        ]);
    }
}
