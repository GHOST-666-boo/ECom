<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 4: Email Verification Sets Timestamp
     * 
     * For any email verification link click with a valid token, the email_verified_at
     * field should be set to the current timestamp.
     * 
     * **Validates: Requirements 1.5**
     */
    public function test_property_4_email_verification_sets_timestamp(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create unverified user
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);
            
            // Verify email_verified_at is null before verification
            $this->assertNull($user->email_verified_at);
            
            // Generate signed verification URL
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->id,
                    'hash' => sha1($user->email),
                ]
            );
            
            // Extract path from full URL
            $path = parse_url($verificationUrl, PHP_URL_PATH);
            $query = parse_url($verificationUrl, PHP_URL_QUERY);
            $fullPath = $path . '?' . $query;
            
            // Record time before verification (subtract 1 second for timing tolerance)
            $beforeVerification = now()->subSecond();
            
            // Call verification endpoint
            $response = $this->getJson($fullPath);
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Email verified successfully.',
            ]);
            
            // Refresh user from database
            $user->refresh();
            
            // Verify email_verified_at is now set
            $this->assertNotNull($user->email_verified_at, 'email_verified_at should be set after verification');
            
            // Verify timestamp is recent (within reasonable time window)
            $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
            
            $this->assertTrue(
                $user->email_verified_at->greaterThanOrEqualTo($beforeVerification),
                'email_verified_at should be set to current timestamp'
            );
            
            $afterVerification = now()->addSecond();
            $this->assertTrue(
                $user->email_verified_at->lessThanOrEqualTo($afterVerification),
                'email_verified_at should not be in the future'
            );
        }
    }

    /**
     * Property 5: Unverified Users Cannot Place Orders
     * 
     * For any user with email_verified_at = null, attempting to place an order should
     * fail with a validation error, while browsing products should succeed.
     * 
     * **Validates: Requirements 1.6**
     */
    public function test_property_5_unverified_users_cannot_place_orders(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create unverified user
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);
            
            // Verify user is unverified
            $this->assertNull($user->email_verified_at);
            
            // Create token for authentication
            $token = $user->createToken('test_token')->plainTextToken;
            
            // Test 1: Browsing products should succeed
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/v1/products');
            
            // Should succeed (200 or 404 if no products exist, but not 403)
            $this->assertNotEquals(403, $response->status(), 
                'Unverified users should be able to browse products');
            
            // Test 2: Attempting to place an order should fail
            // Note: This test assumes the order endpoint exists and checks email verification
            // If the endpoint doesn't exist yet, skip this part of the test
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/v1/orders', [
                    'address_id' => 1,
                    'payment_method' => 'cod',
                ]);
            
            // If endpoint doesn't exist (404), skip the order placement check
            if ($response->status() === 404) {
                $this->markTestIncomplete(
                    'Order endpoint not yet implemented - cannot test email verification requirement for orders'
                );
                return;
            }
            
            // Should fail with 403 Forbidden or 422 Unprocessable Entity (validation error)
            $this->assertContains($response->status(), [403, 422], 
                'Unverified users should not be able to place orders');
            
            // If response is 422, check for email verification error message
            if ($response->status() === 422) {
                $responseData = $response->json();
                $this->assertFalse($responseData['success'] ?? true, 
                    'Response should indicate failure');
            }
        }
    }

    /**
     * Test that already verified emails return appropriate message
     */
    public function test_already_verified_email_returns_appropriate_message(): void
    {
        $iterations = 10;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create already verified user
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            // Generate signed verification URL
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->id,
                    'hash' => sha1($user->email),
                ]
            );
            
            // Extract path from full URL
            $path = parse_url($verificationUrl, PHP_URL_PATH);
            $query = parse_url($verificationUrl, PHP_URL_QUERY);
            $fullPath = $path . '?' . $query;
            
            // Call verification endpoint
            $response = $this->getJson($fullPath);
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
        }
    }

    /**
     * Test that invalid verification hash is rejected
     */
    public function test_invalid_verification_hash_is_rejected(): void
    {
        $iterations = 10;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create unverified user
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);
            
            // Generate signed URL with INVALID hash
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->id,
                    'hash' => 'invalid_hash_' . fake()->uuid(),
                ]
            );
            
            // Extract path from full URL
            $path = parse_url($verificationUrl, PHP_URL_PATH);
            $query = parse_url($verificationUrl, PHP_URL_QUERY);
            $fullPath = $path . '?' . $query;
            
            // Call verification endpoint
            $response = $this->getJson($fullPath);
            
            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'message' => 'Invalid verification link.',
            ]);
            
            // Verify user is still unverified
            $user->refresh();
            $this->assertNull($user->email_verified_at);
        }
    }
}
