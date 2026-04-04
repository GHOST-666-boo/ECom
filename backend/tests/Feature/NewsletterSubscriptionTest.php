<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_to_newsletter()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully subscribed to newsletter',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'email',
                    'subscribed_at',
                ],
                'meta',
            ]);

        // Verify subscription was created in database
        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email' => 'subscriber@example.com',
        ]);

        // Verify unsubscribe token was generated
        $subscription = NewsletterSubscription::where('email', 'subscriber@example.com')->first();
        $this->assertNotNull($subscription->unsubscribe_token);
        $this->assertEquals(64, strlen($subscription->unsubscribe_token));

        // Verify confirmation email was sent
        Notification::assertSentOnDemand(
            NewsletterConfirmationNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'subscriber@example.com';
            }
        );
    }

    public function test_newsletter_subscription_requires_email()
    {
        $response = $this->postJson('/api/v1/newsletter/subscribe', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['The email field is required.'],
                ],
            ]);
    }

    public function test_newsletter_subscription_validates_email_format()
    {
        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Please provide a valid email address.'],
                ],
            ]);
    }

    public function test_newsletter_subscription_prevents_duplicates()
    {
        // Create existing subscription
        NewsletterSubscription::create([
            'email' => 'existing@example.com',
            'unsubscribe_token' => 'existing-token-123',
            'subscribed_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['This email is already subscribed to the newsletter.'],
                ],
            ]);

        // Verify only one subscription exists
        $this->assertEquals(1, NewsletterSubscription::where('email', 'existing@example.com')->count());
    }

    public function test_newsletter_subscription_does_not_require_authentication()
    {
        Notification::fake();

        // Make request without authentication
        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'public@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email' => 'public@example.com',
        ]);
    }

    public function test_newsletter_confirmation_email_includes_unsubscribe_link()
    {
        Notification::fake();

        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'test@example.com',
        ]);

        $subscription = NewsletterSubscription::where('email', 'test@example.com')->first();

        Notification::assertSentOnDemand(
            NewsletterConfirmationNotification::class,
            function ($notification, $channels, $notifiable) use ($subscription) {
                $mailMessage = $notification->toMail($notifiable);
                $expectedUrl = url('/api/v1/newsletter/unsubscribe/' . $subscription->unsubscribe_token);
                
                // Check that the action URL contains the unsubscribe token
                return $notifiable->routes['mail'] === 'test@example.com' &&
                       $mailMessage->actionUrl === $expectedUrl;
            }
        );
    }

    public function test_newsletter_subscription_generates_unique_token()
    {
        Notification::fake();

        // Create first subscription
        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'user1@example.com',
        ]);

        // Create second subscription
        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'user2@example.com',
        ]);

        $subscription1 = NewsletterSubscription::where('email', 'user1@example.com')->first();
        $subscription2 = NewsletterSubscription::where('email', 'user2@example.com')->first();

        // Verify tokens are unique
        $this->assertNotEquals($subscription1->unsubscribe_token, $subscription2->unsubscribe_token);
    }

    public function test_newsletter_subscription_stores_subscribed_at_timestamp()
    {
        Notification::fake();

        $beforeSubscription = now()->subSecond();

        $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'timestamp@example.com',
        ]);

        $afterSubscription = now()->addSecond();

        $subscription = NewsletterSubscription::where('email', 'timestamp@example.com')->first();

        $this->assertNotNull($subscription->subscribed_at);
        $this->assertTrue(
            $subscription->subscribed_at->greaterThanOrEqualTo($beforeSubscription) &&
            $subscription->subscribed_at->lessThanOrEqualTo($afterSubscription)
        );
    }
}
