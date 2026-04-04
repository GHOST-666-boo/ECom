<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewsletterFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_newsletter_subscription_and_unsubscription_flow()
    {
        Notification::fake();

        // Step 1: Subscribe to newsletter
        $subscribeResponse = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'flow-test@example.com',
        ]);

        $subscribeResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully subscribed to newsletter',
            ]);

        // Verify subscription was created
        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email' => 'flow-test@example.com',
        ]);

        // Get the subscription and token
        $subscription = NewsletterSubscription::where('email', 'flow-test@example.com')->first();
        $this->assertNotNull($subscription);
        $this->assertNotNull($subscription->unsubscribe_token);

        // Verify confirmation email was sent with unsubscribe link
        Notification::assertSentOnDemand(
            NewsletterConfirmationNotification::class,
            function ($notification, $channels, $notifiable) use ($subscription) {
                $mailMessage = $notification->toMail($notifiable);
                $expectedUrl = url('/api/v1/newsletter/unsubscribe/' . $subscription->unsubscribe_token);
                
                return $notifiable->routes['mail'] === 'flow-test@example.com' &&
                       $mailMessage->actionUrl === $expectedUrl;
            }
        );

        // Step 2: Unsubscribe using the token from the email
        $unsubscribeResponse = $this->getJson('/api/v1/newsletter/unsubscribe/' . $subscription->unsubscribe_token);

        $unsubscribeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully unsubscribed from newsletter',
                'data' => [
                    'email' => 'flow-test@example.com',
                ],
            ]);

        // Verify subscription was deleted
        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email' => 'flow-test@example.com',
        ]);

        // Step 3: Verify the token cannot be used again
        $secondUnsubscribeResponse = $this->getJson('/api/v1/newsletter/unsubscribe/' . $subscription->unsubscribe_token);

        $secondUnsubscribeResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid unsubscribe token',
            ]);
    }
}
