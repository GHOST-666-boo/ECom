<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Notifications\PaymentFailedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Razorpay webhook notifications.
     * 
     * This endpoint receives payment notifications from Razorpay and updates
     * order status accordingly. It verifies webhook signatures using HMAC SHA-256
     * for security and implements idempotency to handle duplicate webhooks.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function razorpay(Request $request)
    {
        // Get webhook secret from environment
        $webhookSecret = config('services.razorpay.webhook_secret');
        
        // Get signature from headers
        $signature = $request->header('X-Razorpay-Signature');
        
        // Get raw payload
        $payload = $request->getContent();
        
        // Verify webhook signature using HMAC SHA-256
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            // Log security event for invalid signature
            Log::channel('security')->warning('Invalid Razorpay webhook signature', [
                'ip' => $request->ip(),
                'payload' => $payload,
                'signature' => $signature,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 400);
        }
        
        // Parse webhook payload
        $data = $request->all();
        $event = $data['event'] ?? null;
        
        // Handle payment.captured event (successful payment)
        if ($event === 'payment.captured') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? null;
            
            if (!$paymentEntity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook payload',
                ], 400);
            }
            
            $paymentId = $paymentEntity['id'] ?? null;
            $orderId = $paymentEntity['notes']['order_id'] ?? null;
            
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID not found in webhook payload',
                ], 400);
            }
            
            // Find the order
            $order = Order::find($orderId);
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }
            
            // Idempotency check: if order already confirmed, skip processing
            if ($order->status === 'confirmed') {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook already processed',
                ], 200);
            }
            
            // Update order status to confirmed and store payment_id
            $order->update([
                'status' => 'confirmed',
                'payment_id' => $paymentId,
            ]);
            
            Log::info('Razorpay payment successful', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
            ], 200);
        }
        
        // Handle payment.failed event
        if ($event === 'payment.failed') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? null;
            
            if (!$paymentEntity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook payload',
                ], 400);
            }
            
            $orderId = $paymentEntity['notes']['order_id'] ?? null;
            
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID not found in webhook payload',
                ], 400);
            }
            
            // Find the order
            $order = Order::with(['orderItems.product', 'user'])->find($orderId);
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }
            
            // Keep order status as pending (allow retry)
            Log::info('Razorpay payment failed', [
                'order_id' => $orderId,
                'error' => $paymentEntity['error_description'] ?? 'Unknown error',
            ]);
            
            // Restore cart with order items
            $this->restoreCart($order);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment failure recorded',
            ], 200);
        }
        
        // For other events, just acknowledge receipt
        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ], 200);
    }

    /**
     * Restore cart with order items after payment failure.
     * Skip out-of-stock products and notify customer.
     * 
     * @param Order $order
     * @return void
     */
    private function restoreCart(Order $order)
    {
        $user = $order->user;
        
        // Get or create cart for the user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        
        $skippedItems = [];
        $restoredItems = [];
        
        foreach ($order->orderItems as $orderItem) {
            $product = $orderItem->product;
            
            // Skip if product doesn't exist or is out of stock
            if (!$product || $product->stock < 1) {
                $skippedItems[] = [
                    'name' => $product ? $product->name : 'Unknown Product',
                    'quantity' => $orderItem->quantity,
                ];
                continue;
            }
            
            // Check if product already exists in cart
            $existingCartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            if ($existingCartItem) {
                // Increment quantity, but don't exceed stock
                $newQuantity = min(
                    $existingCartItem->quantity + $orderItem->quantity,
                    $product->stock
                );
                $existingCartItem->update(['quantity' => $newQuantity]);
                $restoredItems[] = [
                    'name' => $product->name,
                    'quantity' => $orderItem->quantity,
                ];
            } else {
                // Create new cart item, but don't exceed stock
                $quantity = min($orderItem->quantity, $product->stock);
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
                $restoredItems[] = [
                    'name' => $product->name,
                    'quantity' => $quantity,
                ];
            }
        }
        
        // Send notification email about failed payment and cart restoration
        try {
            $user->notify(new PaymentFailedNotification($order, $restoredItems, $skippedItems));
        } catch (\Exception $e) {
            Log::error('Failed to send payment failure notification', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        Log::info('Cart restored after payment failure', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'restored_items' => count($restoredItems),
            'skipped_items' => count($skippedItems),
        ]);
    }
}
