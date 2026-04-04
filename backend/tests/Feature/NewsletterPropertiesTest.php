<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewsletterPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: artisan-kala-ecommerce, Property 95: Newsletter Email Format Validation
     * 
     * **Validates: Requirements 16.2**
     * 
     * For any newsletter subscription request with an invalid email format,
     * the request should fail with a validation error.
     */
    public function test_property_95_newsletter_email_format_validation()
    {
        $invalidEmails = [
            'not-an-email',
            'missing@',
            '@nodomain.com',
            'double@@domain.com',
            'trailing.dot.@domain.com',
            'just-text',
            'missing-at-sign.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $response = $this->postJson('/api/v1/newsletter/subscribe', [
                'email' => $invalidEmail,
            ]);

            if ($response->status() !== 422) {
                $this->fail("Email '$invalidEmail' should have failed validation but got status " . $response->status());
            }

            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
            ]);
            $response->assertJsonStructure([
                'errors' => ['email'],
            ]);
        }
        
        // Test empty email separately (required validation)
        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => '',
        ]);
        
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Feature: artisan-kala-ecommerce, Property 96: Duplicate Newsletter Subscription Prevention
     * 
     * **Validates: Requirements 16.4**
     * 
     * For any newsletter subscription request with an email that already exists
     * in newsletter_subscriptions, the request should fail with a validation error.
     */
    public function test_property_96_duplicate_newsletter_subscription_prevention()
    {
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $email = fake()->unique()->safeEmail();

            // First subscription should succeed
            NewsletterSubscription::create([
                'email' => $email,
                'unsubscribe_token' => fake()->sha256(),
                'subscribed_at' => now(),
            ]);

            // Second subscription with same email should fail
            $response = $this->postJson('/api/v1/newsletter/subscribe', [
                'email' => $email,
            ]);

            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
            $response->assertJsonStructure([
                'errors' => ['email'],
            ]);

            // Verify only one subscription exists
            $this->assertEquals(1, NewsletterSubscription::where('email', $email)->count());
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property 97: Newsletter Confirmation Email Sent
     * 
     * **Validates: Requirements 16.5**
     * 
     * For any successful newsletter subscription, a confirmation email with
     * an unsubscribe link should be sent to the subscriber.
     */
    public function test_property_97_newsletter_confirmation_email_sent()
    {
        $iterations = 20;

        for ($i = 0; $i < $iterations; $i++) {
            Notification::fake();

            $email = fake()->unique()->safeEmail();

            $response = $this->postJson('/api/v1/newsletter/subscribe', [
                'email' => $email,
            ]);

            $response->assertStatus(201);

            // Verify confirmation email was sent
            Notification::assertSentOnDemand(
                NewsletterConfirmationNotification::class,
                function ($notification, $channels, $notifiable) use ($email) {
                    return $notifiable->routes['mail'] === $email;
                }
            );

            // Verify email contains unsubscribe link
            $subscription = NewsletterSubscription::where('email', $email)->first();
            $this->assertNotNull($subscription);

            Notification::assertSentOnDemand(
                NewsletterConfirmationNotification::class,
                function ($notification, $channels, $notifiable) use ($subscription) {
                    $mailMessage = $notification->toMail($notifiable);
                    $expectedUrl = url('/api/v1/newsletter/unsubscribe/' . $subscription->unsubscribe_token);
                    return $mailMessage->actionUrl === $expectedUrl;
                }
            );
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property 98: Unique Signed Unsubscribe Token
     * 
     * **Validates: Requirements 16.6**
     * 
     * For any newsletter subscription, a unique signed unsubscribe token
     * should be generated and stored.
     */
    public function test_property_98_unique_signed_unsubscribe_token()
    {
        Notification::fake();

        $iterations = 50;
        $tokens = [];

        for ($i = 0; $i < $iterations; $i++) {
            $email = fake()->unique()->safeEmail();

            $response = $this->postJson('/api/v1/newsletter/subscribe', [
                'email' => $email,
            ]);

            $response->assertStatus(201);

            $subscription = NewsletterSubscription::where('email', $email)->first();

            // Verify token exists and is not empty
            $this->assertNotNull($subscription->unsubscribe_token);
            $this->assertNotEmpty($subscription->unsubscribe_token);

            // Verify token is unique (not in our collection)
            $this->assertNotContains($subscription->unsubscribe_token, $tokens);

            // Add to collection for uniqueness check
            $tokens[] = $subscription->unsubscribe_token;

            // Verify token is stored in database
            $this->assertDatabaseHas('newsletter_subscriptions', [
                'email' => $email,
                'unsubscribe_token' => $subscription->unsubscribe_token,
            ]);
        }

        // Verify all tokens are unique
        $this->assertEquals($iterations, count(array_unique($tokens)));
    }

    /**
     * Feature: artisan-kala-ecommerce, Property 99: Unsubscribe Token Validation
     * 
     * **Validates: Requirements 16.8**
     * 
     * For any unsubscribe request with an invalid or unsigned token,
     * the request should fail with a validation error.
     */
    public function test_property_99_unsubscribe_token_validation()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random invalid tokens (non-empty to avoid 404)
            $invalidTokens = [
                fake()->uuid(),
                fake()->sha256(),
                fake()->md5(),
                fake()->regexify('[A-Za-z0-9]{32}'),
                'invalid-token-' . $i,
                'short',
                str_repeat('a', 128),
                fake()->word(),
            ];

            $invalidToken = $invalidTokens[array_rand($invalidTokens)];

            // Attempt to unsubscribe with invalid token
            $response = $this->getJson('/api/v1/newsletter/unsubscribe/' . urlencode($invalidToken));

            // Should fail with validation error
            $response->assertStatus(422);
            $response->assertJson([
                'success' => false,
                'message' => 'Invalid unsubscribe token',
            ]);
            $response->assertJsonStructure([
                'errors' => ['token'],
            ]);
        }
    }

    /**
     * Feature: artisan-kala-ecommerce, Property 100: Newsletter Operations Without Authentication
     * 
     * **Validates: Requirements 16.9**
     * 
     * For any newsletter subscription or unsubscription request,
     * the operation should succeed without requiring authentication.
     */
    public function test_property_100_newsletter_operations_without_authentication()
    {
        Notification::fake();

        $iterations = 20;

        for ($i = 0; $i < $iterations; $i++) {
            $email = fake()->unique()->safeEmail();

            // Make request without any authentication headers
            $response = $this->postJson('/api/v1/newsletter/subscribe', [
                'email' => $email,
            ]);

            // Should succeed without authentication
            $response->assertStatus(201);
            $response->assertJson([
                'success' => true,
            ]);

            // Verify subscription was created
            $this->assertDatabaseHas('newsletter_subscriptions', [
                'email' => $email,
            ]);
        }

        // Test unsubscribe without authentication
        for ($i = 0; $i < 10; $i++) {
            $email = fake()->unique()->safeEmail();
            $token = fake()->sha256();

            // Create subscription
            NewsletterSubscription::create([
                'email' => $email,
                'unsubscribe_token' => $token,
                'subscribed_at' => now(),
            ]);

            // Unsubscribe without authentication
            $response = $this->getJson('/api/v1/newsletter/unsubscribe/' . $token);

            // Should succeed without authentication
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
            ]);

            // Verify subscription was deleted
            $this->assertDatabaseMissing('newsletter_subscriptions', [
                'email' => $email,
            ]);
        }
    }
}
