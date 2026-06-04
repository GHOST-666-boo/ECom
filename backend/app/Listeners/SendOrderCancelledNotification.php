<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Notifications\OrderCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderCancelledNotification implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job (exponential backoff).
     *
     * @var array
     */
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        try {
            $event->order->user->notify(new OrderCancelledNotification($event->order, $event->reason));
            
            Log::info('Order cancelled notification sent', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'user_id' => $event->order->user_id,
                'reason' => $event->reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order cancelled notification', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw to allow Laravel's queue retry mechanism to handle retries
            throw $e;
        }
    }
}
