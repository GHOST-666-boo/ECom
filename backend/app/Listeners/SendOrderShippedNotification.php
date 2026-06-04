<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Notifications\OrderShippedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderShippedNotification implements ShouldQueue
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
    public function handle(OrderShipped $event): void
    {
        try {
            $event->order->user->notify(new OrderShippedNotification($event->order));
            
            Log::info('Order shipped notification sent', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'user_id' => $event->order->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order shipped notification', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw to allow Laravel's queue retry mechanism to handle retries
            throw $e;
        }
    }
}
