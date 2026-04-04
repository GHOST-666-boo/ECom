<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LoginTokenPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 6: Session Token Expiry
     * 
     * For any successful login, the returned Sanctum token should have an expiry date
     * exactly 7 days from the current timestamp.
     * 
     * **Validates: Requirements 1.7**
     */
    public function test_property_6_session_token_expiry(): void
    {
        // Disable rate limiting for this test
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create a user with verified email
            $password = 'password123';
            $user = User::factory()->create([
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);
            
            // Record the time before login
            $beforeLogin = now();
            
            // Login
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => $password,
            ]);
            
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);
            
            // Get the token from response
            $tokenString = $response->json('data.token');
            $this->assertNotEmpty($tokenString);
            
            // Extract the token ID from the plain text token (format: {id}|{token})
            $tokenParts = explode('|', $tokenString);
            $tokenId = $tokenParts[0];
            
            // Retrieve the token from database
            $token = PersonalAccessToken::find($tokenId);
            $this->assertNotNull($token);
            
            // Verify token has an expiry date
            $this->assertNotNull($token->expires_at);
            
            // Calculate expected expiry (7 days from now)
            $expectedExpiry = $beforeLogin->copy()->addDays(7);
            $afterLogin = now();
            
            // Token expiry should be within the time window of the login request
            // Allow a 2-second tolerance for test execution time
            $this->assertTrue(
                $token->expires_at->between(
                    $expectedExpiry->copy()->subSeconds(2),
                    $afterLogin->copy()->addDays(7)->addSeconds(2)
                ),
                "Token expiry {$token->expires_at} is not within 7 days of login time"
            );
            
            // Verify it's exactly 7 days (within reasonable tolerance)
            // Use diffInSeconds and check it's within 7 days ± 2 seconds
            $expectedSeconds = 7 * 24 * 60 * 60; // 7 days in seconds
            $actualSeconds = $beforeLogin->diffInSeconds($token->expires_at, false);
            $this->assertTrue(
                abs($actualSeconds - $expectedSeconds) <= 2,
                "Token expiry is not exactly 7 days from login. Expected {$expectedSeconds} seconds, got {$actualSeconds} seconds"
            );
        }
    }

    /**
     * Property 11: Expired Token Returns 401
     * 
     * For any API request with an expired Sanctum token, the response should be
     * HTTP 401 Unauthorized.
     * 
     * **Validates: Requirements 1.15**
     */
    public function test_property_11_expired_token_returns_401(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create a user
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            // Create a token that expired in the past
            // Randomly expire between 1 second and 30 days ago
            $expiredMinutesAgo = rand(1, 43200); // 1 minute to 30 days
            $token = $user->createToken(
                'expired_token',
                ['*'],
                now()->subMinutes($expiredMinutesAgo)
            );
            
            $plainTextToken = $token->plainTextToken;
            
            // Verify the token is indeed expired
            $tokenModel = $token->accessToken;
            $this->assertTrue(
                $tokenModel->expires_at->isPast(),
                "Token should be expired but expires_at is in the future"
            );
            
            // Try to access an authenticated endpoint with the expired token
            $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextToken)
                ->getJson('/api/v1/auth/user');
            
            // Should return 401 Unauthorized
            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        }
    }
}
