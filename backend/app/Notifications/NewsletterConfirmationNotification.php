<?php

namespace App\Notifications;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsletterConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public NewsletterSubscription $subscription
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
        $unsubscribeUrl = url('/api/v1/newsletter/unsubscribe/' . $this->subscription->unsubscribe_token);
        
        return (new MailMessage)
            ->subject('Welcome to Artisan Kala Newsletter')
            ->greeting('Thank you for subscribing!')
            ->line('You have successfully subscribed to the Artisan Kala newsletter.')
            ->line('You will receive updates about new products, special offers, and artisan stories.')
            ->line('If you wish to unsubscribe at any time, click the link below:')
            ->action('Unsubscribe', $unsubscribeUrl)
            ->line('Thank you for supporting our artisans!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $this->subscription->email,
            'subscribed_at' => $this->subscription->subscribed_at,
        ];
    }
}
