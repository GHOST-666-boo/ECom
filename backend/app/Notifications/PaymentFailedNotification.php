<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public array $restoredItems,
        public array $skippedItems
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Payment Failed - Order ' . $this->order->order_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We\'re sorry, but your payment for order ' . $this->order->order_number . ' has failed.')
            ->line('Don\'t worry - we\'ve restored your cart with the items from this order so you can try again.');
        
        // Add restored items information
        if (count($this->restoredItems) > 0) {
            $message->line('**Items restored to your cart:**');
            foreach ($this->restoredItems as $item) {
                $message->line('- ' . $item['name'] . ' (Quantity: ' . $item['quantity'] . ')');
            }
        }
        
        // Add skipped items information if any
        if (count($this->skippedItems) > 0) {
            $message->line('**Items that could not be restored (out of stock):**');
            foreach ($this->skippedItems as $item) {
                $message->line('- ' . $item['name'] . ' (Quantity: ' . $item['quantity'] . ')');
            }
        }
        
        $message->action('View Your Cart', url('/cart'))
            ->line('You can review your cart and try completing your purchase again.')
            ->line('If you have any questions, please don\'t hesitate to contact us.');
        
        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'restored_items' => $this->restoredItems,
            'skipped_items' => $this->skippedItems,
        ];
    }
}
