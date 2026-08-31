<?php

namespace App\Console\Commands;

use App\Events\OrderCancelled;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCancelPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cancel pending orders older than 48 hours and restore product stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-cancel job for pending orders...');

        // Find orders with status 'pending' and created_at > 48 hours ago
        $cutoffTime = now()->subHours(48);
        
        $pendingOrders = Order::with(['orderItems', 'user'])
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoffTime)
            ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('No pending orders found older than 48 hours.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pendingOrders->count()} pending orders to cancel.");

        $cancelledCount = 0;
        $failedCount = 0;

        foreach ($pendingOrders as $order) {
            try {
                DB::transaction(function () use ($order): void {
                    /** @var Order $order */
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
                            $product->stock = $product->stock + $orderItem->quantity;
                            $product->save();
                        }
                    }
                });

                // Queue cancellation notification email with reason "payment timeout"
                OrderCancelled::dispatch($order, 'payment timeout');

                $this->info("Cancelled order {$order->order_number}");
                
                Log::info('Order auto-cancelled due to payment timeout', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'created_at' => $order->created_at,
                ]);

                $cancelledCount++;
            } catch (\Exception $e) {
                $this->error("Failed to cancel order {$order->order_number}: {$e->getMessage()}");
                
                Log::error('Auto-cancel order failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);

                $failedCount++;
            }
        }

        $this->info("Auto-cancel job completed. Cancelled: {$cancelledCount}, Failed: {$failedCount}");

        return $failedCount > 0 && $cancelledCount === 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
