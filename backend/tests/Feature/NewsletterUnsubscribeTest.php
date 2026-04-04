<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_unsubscribe_with_valid_token()
    {
        // Create a subscription
        $subscription = NewsletterSubscription::create([
            'email' => 'unsubscribe@example.com',
            'unsubscribe_token' => 'valid-token-12345',
            'subscribed_at' => now(),
        ]);

        // Unsubscribe using the token
        $response = $this->getJson('/api/v1/newsletter/unsubscribe/valid-token-12345');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully unsubscribed from newsletter',
                'data' => [
                    'email' => 'unsubscribe@example.com',
                ],
            ]);

        // Verify subscription was deleted
        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email' => 'unsubscribe@example.com',
        ]);
    }

    public function test_unsubscribe_with_invalid_token_returns_422()
    {
        $response = $this->getJson('/api/v1/newsletter/unsubscribe/invalid-token-xyz');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid unsubscribe token',
                'errors' => [
                    'token' => ['The provided unsubscribe token is invalid or has already been used.'],
                ],
            ]);
    }

    public function test_unsubscribe_does_not_require_authentication()
    {
        // Create a subscription
        $subscription = NewsletterSubscription::create([
            'email' => 'public-unsubscribe@example.com',
            'unsubscribe_token' => 'public-token-67890',
            'subscribed_at' => now(),
        ]);

        // Unsubscribe without authentication
        $response = $this->getJson('/api/v1/newsletter/unsubscribe/public-token-67890');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify subscription was deleted
        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email' => 'public-unsubscribe@example.com',
        ]);
    }

    public function test_unsubscribe_token_can_only_be_used_once()
    {
        // Create a subscription
        $subscription = NewsletterSubscription::create([
            'email' => 'once@example.com',
            'unsubscribe_token' => 'once-token-11111',
            'subscribed_at' => now(),
        ]);

        // First unsubscribe - should succeed
        $response1 = $this->getJson('/api/v1/newsletter/unsubscribe/once-token-11111');
        $response1->assertStatus(200);

        // Second unsubscribe with same token - should fail
        $response2 = $this->getJson('/api/v1/newsletter/unsubscribe/once-token-11111');
        $response2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid unsubscribe token',
            ]);
    }
}
