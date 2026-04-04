<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscribeRequest;
use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter
     */
    public function subscribe(NewsletterSubscribeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Check if email already exists
        if (NewsletterSubscription::where('email', $validated['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['This email is already subscribed to the newsletter.']
                ],
                'data' => null
            ], 422);
        }
        
        // Generate unique signed unsubscribe token
        $unsubscribeToken = Str::random(64);
        
        // Create subscription
        $subscription = NewsletterSubscription::create([
            'email' => $validated['email'],
            'unsubscribe_token' => $unsubscribeToken,
            'subscribed_at' => now(),
        ]);
        
        // Queue confirmation email with unsubscribe link
        Notification::route('mail', $subscription->email)
            ->notify(new NewsletterConfirmationNotification($subscription));
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to newsletter',
            'data' => [
                'email' => $subscription->email,
                'subscribed_at' => $subscription->subscribed_at,
            ],
            'meta' => null
        ], 201);
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(string $token): JsonResponse
    {
        // Validate unsubscribe token signature (verify token exists in database)
        $subscription = NewsletterSubscription::where('unsubscribe_token', $token)->first();
        
        // If token invalid, return HTTP 422
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid unsubscribe token',
                'errors' => [
                    'token' => ['The provided unsubscribe token is invalid or has already been used.']
                ],
                'data' => null
            ], 422);
        }
        
        // Delete subscription record
        $subscription->delete();
        
        // Return success message
        return response()->json([
            'success' => true,
            'message' => 'Successfully unsubscribed from newsletter',
            'data' => [
                'email' => $subscription->email,
            ],
            'meta' => null
        ], 200);
    }
}
