<?php

namespace App\Http\Controllers;

use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class OrderController extends Controller
{
    /**
     * Get order history for authenticated user with cursor-based pagination.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get orders with order items and products for displaying images
        $orders = Order::with(['orderItems.product:id,name,slug,images'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->cursorPaginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'orders' => $orders->items(),  // Direct field
            'meta' => [
                'next_cursor' => $orders->nextCursor()?->encode(),
                'per_page' => $orders->perPage(),
            ],
        ], 200);
    }

    /**
     * Get order details for a specific order.
     * Returns order with items, product information, and address snapshot.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Get order with order items and product details
        $order = Order::with(['orderItems.product'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        // Return 404 if order not found or does not belong to user
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'order' => [  // Direct field
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_id' => $order->payment_id,
                'payment_status' => $order->payment_status,
                'tracking_number' => $order->tracking_number,
                'courier_name' => $order->courier_name,
                'total' => $order->total,
                'address_snapshot' => $order->address_snapshot,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'images' => $item->product->images,
                            'image_urls' => $item->product->image_urls,
                        ] : null,
                    ];
                }),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ],
        ], 200);
    }

    /**
     * Place an order with address and payment method.
     * Requires authentication and email verification.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user email is verified FIRST (before any other validation)
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification required',
                'errors' => [
                    'email' => ['Please verify your email before placing an order'],
                ],
            ], 422);
        }

        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cod,razorpay',
        ]);

        // Verify address belongs to user
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or does not belong to you',
                'errors' => [
                    'address_id' => ['Address not found or does not belong to you'],
                ],
            ], 422);
        }

        // Get user cart with items
        $cart = Cart::with(['cartItems.product'])
            ->where('user_id', $user->id)
            ->first();

        // Validate cart is not empty
        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
                'errors' => [
                    'cart' => ['Your cart is empty. Please add items before placing an order.'],
                ],
            ], 422);
        }

        // Validate all cart items have sufficient stock
        $outOfStockProducts = [];
        foreach ($cart->cartItems as $cartItem) {
            if (!$cartItem->product) {
                $outOfStockProducts[] = 'Product ID ' . $cartItem->product_id . ' (not found)';
                continue;
            }
            
            if ($cartItem->product->stock < $cartItem->quantity) {
                $outOfStockProducts[] = $cartItem->product->name . ' (available: ' . $cartItem->product->stock . ', requested: ' . $cartItem->quantity . ')';
            }
        }

        if (!empty($outOfStockProducts)) {
            return response()->json([
                'success' => false,
                'message' => 'Some products are out of stock',
                'errors' => [
                    'stock' => $outOfStockProducts,
                ],
            ], 422);
        }

        // Use database transaction to ensure atomicity
        try {
            $order = DB::transaction(function () use ($user, $cart, $address, $validated) {
                // Generate unique order_number in format ORD-YYYYNNNNN
                $orderNumber = $this->generateOrderNumber();

                // Create address snapshot
                $addressSnapshot = [
                    'name' => $address->name,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pincode' => $address->pincode,
                ];

                // Calculate total order amount
                $total = 0;
                $orderItemsData = [];

                foreach ($cart->cartItems as $cartItem) {
                    $itemTotal = $cartItem->quantity * $cartItem->product->price;
                    $total += $itemTotal;

                    $orderItemsData[] = [
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->product->price,
                    ];
                }

                // Determine initial order status
                $status = $validated['payment_method'] === 'cod' ? 'confirmed' : 'pending';

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => $orderNumber,
                    'status' => $status,
                    'payment_method' => $validated['payment_method'],
                    'total' => $total,
                    'address_snapshot' => $addressSnapshot,
                ]);

                // Create order items
                foreach ($orderItemsData as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                    ]);
                }

                // Decrement stock atomically with pessimistic locking (FOR UPDATE)
                // Only decrement stock if order is confirmed (COD orders)
                if ($status === 'confirmed') {
                    foreach ($orderItemsData as $itemData) {
                        // Use lockForUpdate to acquire pessimistic lock on product row
                        $product = Product::where('id', $itemData['product_id'])
                            ->lockForUpdate()
                            ->first();

                        if (!$product) {
                            throw new \Exception("Product not found: {$itemData['product_id']}");
                        }

                        // Decrement stock atomically
                        $product->stock -= $itemData['quantity'];
                        
                        // Ensure stock doesn't go negative (double-check)
                        if ($product->stock < 0) {
                            throw new \Exception("Insufficient stock for product: {$product->name}");
                        }
                        
                        $product->save();
                    }
                }

                // Clear user cart
                $cart->cartItems()->delete();

                return $order;
            });

            // Queue order confirmation email
            OrderPlaced::dispatch($order);

            Log::info('Order placed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
            ]);

            // Load order items with product details for response
            $order->load(['orderItems.product']);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order' => [  // Direct field
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'total' => $order->total,
                    'address_snapshot' => $order->address_snapshot,
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'slug' => $item->product->slug,
                                'images' => $item->product->images,
                                'image_urls' => $item->product->image_urls,
                            ] : null,
                        ];
                    }),
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ],
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if this is a lock timeout error
            if ($e->getCode() === '40001' || str_contains($e->getMessage(), 'Lock wait timeout')) {
                Log::warning('Lock timeout during order placement', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Product temporarily unavailable',
                    'errors' => [
                        'stock' => ['Product temporarily unavailable. Please try again.'],
                    ],
                ], 422);
            }

            // Other database errors
            Log::error('Database error during order placement', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Order placement failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate unique order number in format ORD-YYYYNNNNN.
     * 
     * @return string
     */
    private function generateOrderNumber(): string
    {
        $year = date('Y');
        
        // Get the last order number for this year
        $lastOrder = Order::where('order_number', 'like', "ORD-{$year}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastOrder->order_number, -5);
            $newSequence = $lastSequence + 1;
        } else {
            // First order of the year
            $newSequence = 1;
        }

        // Format with leading zeros (5 digits)
        $sequenceFormatted = str_pad($newSequence, 5, '0', STR_PAD_LEFT);

        return "ORD-{$year}{$sequenceFormatted}";
    }

    /**
     * Create Razorpay order for payment collection.
     * Accepts order_id, creates Razorpay order with amount from order total.
     * Returns Razorpay order_id to frontend for payment collection.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRazorpayOrder(Request $request)
    {
        $user = $request->user();

        // Validate request
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Get order and verify it belongs to the authenticated user
        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not belong to you',
                'errors' => [
                    'order_id' => ['Order not found or does not belong to you'],
                ],
            ], 422);
        }

        // Verify order payment method is Razorpay
        if ($order->payment_method !== 'razorpay') {
            return response()->json([
                'success' => false,
                'message' => 'Order payment method is not Razorpay',
                'errors' => [
                    'payment_method' => ['This order does not use Razorpay payment method'],
                ],
            ], 422);
        }

        try {
            // Check if Razorpay credentials are configured
            $keyId = config('services.razorpay.key_id');
            $keySecret = config('services.razorpay.key_secret');

            if (empty($keyId) || empty($keySecret) || 
                $keyId === 'your_razorpay_key_id' || 
                $keySecret === 'your_razorpay_key_secret') {
                Log::error('Razorpay credentials not configured', [
                    'order_id' => $order->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway not configured. Please use Cash on Delivery or contact support.',
                    'errors' => [
                        'payment' => ['Razorpay is not configured. Please use Cash on Delivery.'],
                    ],
                ], 422);
            }

            // Initialize Razorpay API (use app() for dependency injection support)
            $api = app(Api::class);

            // Convert amount to paise (multiply by 100)
            $amountInPaise = (int) ($order->total * 100);

            // Create Razorpay order
            $razorpayOrder = $api->order->create([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $order->order_number,
                'notes' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            Log::info('Razorpay order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'razorpay_order_id' => $razorpayOrder->id,
                'amount' => $amountInPaise,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Razorpay order created successfully',
                'data' => [
                    'razorpay_order_id' => $razorpayOrder->id,
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'order_number' => $order->order_number,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to create Razorpay order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Razorpay order. Please try again.',
            ], 500);
        }
    }

    /**
     * Cancel order (customer only).
     * Allows cancellation only if order status is 'pending'.
     * Restores product stock and queues cancellation notification email.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        // Get order and verify it belongs to the authenticated user
        $order = Order::with(['orderItems'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Check if order status is pending
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled',
                'errors' => [
                    'status' => ["Orders with status '{$order->status}' cannot be cancelled. Only pending orders can be cancelled."],
                ],
            ], 422);
        }

        // Use database transaction to ensure atomicity
        try {
            DB::transaction(function () use ($order) {
                // Update order status to cancelled
                $order->status = 'cancelled';
                $order->save();

                // Restore product stock for all order items
                foreach ($order->orderItems as $orderItem) {
                    // Use lockForUpdate to acquire pessimistic lock on product row
                    $product = Product::where('id', $orderItem->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        // Increment stock atomically
                        $product->stock += $orderItem->quantity;
                        $product->save();
                    }
                }
            });

            Log::info('Order cancelled by customer', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
            ]);

            // Queue cancellation notification email
            OrderCancelled::dispatch($order, 'customer request');

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'updated_at' => $order->updated_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Order cancellation failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order. Please try again.',
            ], 500);
        }
    }

    /**
     * Update order status (admin only).
     * Validates status transitions according to business rules.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        // Check if user is admin
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin role required.',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'status'          => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:100',
            'courier_name'    => 'nullable|string|max:100',
        ]);

        // Get order
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $currentStatus = $order->status;
        $newStatus = $validated['status'];

        // Define valid status transitions
        $validTransitions = [
            'pending'    => ['confirmed', 'cancelled'],
            'confirmed'  => ['processing', 'shipped', 'cancelled'], // Allow direct confirmed → shipped
            'processing' => ['shipped', 'cancelled'],
            'shipped'    => ['delivered'],
            'delivered'  => [],
            'cancelled'  => [],
        ];

        // Check if transition is valid
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition',
                'errors' => [
                    'status' => ["Cannot transition from {$currentStatus} to {$newStatus}"],
                ],
            ], 422);
        }

        // When shipping, tracking number is required
        if ($newStatus === 'shipped') {
            if (empty($validated['tracking_number'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking number is required when marking order as shipped',
                    'errors' => [
                        'tracking_number' => ['Tracking number is required when shipping an order.'],
                    ],
                ], 422);
            }
            $order->tracking_number = $validated['tracking_number'];
            $order->courier_name    = $validated['courier_name'] ?? null;
        }

        // Auto-mark COD as paid when delivered
        if ($newStatus === 'delivered' && $order->payment_method === 'cod') {
            $order->payment_status = 'paid';
        }

        // Update order status
        $order->status = $newStatus;
        $order->save();

        Log::info('Order status updated by admin', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_status' => $currentStatus,
            'new_status' => $newStatus,
            'admin_id' => $user->id,
        ]);

        // Send notification email based on status change
        if ($newStatus === 'shipped') {
            OrderShipped::dispatch($order);
        } elseif ($newStatus === 'delivered') {
            OrderDelivered::dispatch($order);
        } elseif ($newStatus === 'cancelled') {
            OrderCancelled::dispatch($order, 'admin cancellation');
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => [
                'order' => [
                    'id'             => $order->id,
                    'order_number'   => $order->order_number,
                    'status'         => $order->status,
                    'payment_status' => $order->payment_status,
                    'tracking_number'=> $order->tracking_number,
                    'courier_name'   => $order->courier_name,
                    'updated_at'     => $order->updated_at,
                ],
            ],
        ], 200);
    }
}
