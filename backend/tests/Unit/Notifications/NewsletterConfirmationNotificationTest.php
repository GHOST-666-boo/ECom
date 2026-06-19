<?php

namespace Tests\Unit\Notifications;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

class NewsletterConfirmationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_mail_channel(): void
    {
        $subscription = NewsletterSubscription::create([
            'email' => 'sub@example.com',
            'unsubscribe_token' => 'unsub-token',
            'subscribed_at' => now(),
        ]);

        $notification = new NewsletterConfirmationNotification($subscription);
        $notifiable = new AnonymousNotifiable;

        $this->assertEquals(['mail'], $notification->via($notifiable));
    }

    public function test_to_mail_has_correct_subject(): void
    {
        $subscription = NewsletterSubscription::create([
            'email' => 'sub@example.com',
            'unsubscribe_token' => 'unsub-token',
            'subscribed_at' => now(),
        ]);

        $notification = new NewsletterConfirmationNotification($subscription);
        $notifiable = new AnonymousNotifiable;
        $mail = $notification->toMail($notifiable);

        $this->assertEquals('Welcome to Vriddhi Newsletter', $mail->subject);
    }

    public function test_to_array_includes_subscription_data(): void
    {
        $subscription = NewsletterSubscription::create([
            'email' => 'sub@example.com',
            'unsubscribe_token' => 'unsub-token',
            'subscribed_at' => now(),
        ]);

        $notification = new NewsletterConfirmationNotification($subscription);
        $notifiable = new AnonymousNotifiable;
        $array = $notification->toArray($notifiable);

        $this->assertEquals('sub@example.com', $array['email']);
        $this->assertArrayHasKey('subscribed_at', $array);
    }
}
