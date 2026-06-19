<?php

namespace Tests\Unit\Models;

use App\Models\NewsletterSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_subscription_has_fillable_attributes(): void
    {
        $subscription = new NewsletterSubscription;

        $this->assertEquals([
            'email',
            'unsubscribe_token',
            'subscribed_at',
        ], $subscription->getFillable());
    }

    public function test_newsletter_subscription_casts_subscribed_at_to_datetime(): void
    {
        $subscription = NewsletterSubscription::create([
            'email' => 'test@example.com',
            'unsubscribe_token' => 'token123',
            'subscribed_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $subscription->subscribed_at);
    }
}
