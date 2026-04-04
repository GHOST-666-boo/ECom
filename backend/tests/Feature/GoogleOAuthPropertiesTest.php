<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleOAuthPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 7: Google OAuth Token Verification
     * 
     * For any Google id_token submitted to the API, the token should be verified
     * against Google's public keys before creating or retrieving a user.
     * 
     * **Validates: Requirements 1.10**
     */
    public function test_property_7_google_oauth_token_verification(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $googleId = 'google-user-' . fake()->unique()->uuid();
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            $idToken = 'valid-token-' . fake()->uuid();
            
            // Track if verifyIdToken was called
            $verificationCalled = false;
            
            // Mock GoogleAuthService to track verification
            $this->mock(GoogleAuthService::class, function ($mock) use ($idToken, $googleId, $email, $name, &$verificationCalled) {
                $mock->shouldReceive('verifyIdToken')
                    ->once()
                    ->with($idToken)
                    ->andReturnUsing(function () use ($googleId, $email, $name, &$verificationCalled) {
                        $verificationCalled = true;
                        return [
                            'sub' => $googleId,
                            'email' => $email,
                            'name' => $name,
                        ];
                    });
            });
            
            $response = $this->postJson('/api/v1/auth/google', [
                'id_token' => $idToken,
            ]);
            
            // Verify the token verification was called before user creation
            $this->assertTrue($verificationCalled, 'Token verification must be called');
            
            // Verify successful authentication
            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Google authentication successful',
                ]);
            
            // Verify user was created only after verification
            $this->assertDatabaseHas('users', [
                'email' => $email,
                'google_id' => $googleId,
            ]);
        }
    }

    /**
     * Property 8: Google OAuth User Creation
     * 
     * For any valid Google id_token, a user should be created or retrieved by google_id,
     * and a Sanctum token should be returned.
     * 
     * **Validates: Requirements 1.11**
     */
    public function test_property_8_google_oauth_user_creation(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $googleId = 'google-user-' . fake()->unique()->uuid();
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            $idToken = 'valid-token-' . fake()->uuid();
            
            // Mock GoogleAuthService
            $this->mock(GoogleAuthService::class, function ($mock) use ($idToken, $googleId, $email, $name) {
                $mock->shouldReceive('verifyIdToken')
                    ->once()
                    ->with($idToken)
                    ->andReturn([
                        'sub' => $googleId,
                        'email' => $email,
                        'name' => $name,
                    ]);
            });
            
            // First authentication - should create user
            $response = $this->postJson('/api/v1/auth/google', [
                'id_token' => $idToken,
            ]);
            
            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Google authentication successful',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user' => ['id', 'email', 'name', 'google_id'],
                        'token',
                    ],
                ]);
            
            // Verify user was created with google_id
            $user = User::where('google_id', $googleId)->first();
            $this->assertNotNull($user);
            $this->assertEquals($email, $user->email);
            $this->assertEquals($googleId, $user->google_id);
            $this->assertEquals('customer', $user->role);
            
            // Verify Sanctum token was returned
            $token = $response->json('data.token');
            $this->assertIsString($token);
            $this->assertNotEmpty($token);
            
            // Verify no duplicate user was created
            $this->assertEquals(1, User::where('google_id', $googleId)->count());
        }
    }

    /**
     * Property 9: Google OAuth Auto-Verification
     * 
     * For any user authenticated via Google OAuth, the email_verified_at field
     * should be automatically set to the current timestamp.
     * 
     * **Validates: Requirements 1.12**
     */
    public function test_property_9_google_oauth_auto_verification(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $googleId = 'google-user-' . fake()->unique()->uuid();
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            $idToken = 'valid-token-' . fake()->uuid();
            
            $beforeAuth = now();
            
            // Mock GoogleAuthService
            $this->mock(GoogleAuthService::class, function ($mock) use ($idToken, $googleId, $email, $name) {
                $mock->shouldReceive('verifyIdToken')
                    ->once()
                    ->with($idToken)
                    ->andReturn([
                        'sub' => $googleId,
                        'email' => $email,
                        'name' => $name,
                    ]);
            });
            
            $response = $this->postJson('/api/v1/auth/google', [
                'id_token' => $idToken,
            ]);
            
            $afterAuth = now();
            
            $response->assertStatus(200);
            
            // Verify user was created with email_verified_at set
            $user = User::where('google_id', $googleId)->first();
            $this->assertNotNull($user);
            $this->assertNotNull($user->email_verified_at);
            
            // Verify email_verified_at is within reasonable time range (within 5 seconds)
            $this->assertTrue(
                $user->email_verified_at->between($beforeAuth->subSeconds(1), $afterAuth->addSeconds(1)),
                'email_verified_at should be set to current timestamp during authentication'
            );
        }
        
        // Test with existing unverified user
        for ($i = 0; $i < 10; $i++) {
            $googleId = 'google-user-existing-' . fake()->unique()->uuid();
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            
            // Create existing user without email verification
            $existingUser = User::factory()->create([
                'email' => $email,
                'google_id' => null,
                'email_verified_at' => null,
            ]);
            
            $this->assertNull($existingUser->email_verified_at);
            
            $idToken = 'valid-token-existing-' . fake()->uuid();
            $beforeAuth = now();
            
            // Mock GoogleAuthService
            $this->mock(GoogleAuthService::class, function ($mock) use ($idToken, $googleId, $email, $name) {
                $mock->shouldReceive('verifyIdToken')
                    ->once()
                    ->with($idToken)
                    ->andReturn([
                        'sub' => $googleId,
                        'email' => $email,
                        'name' => $name,
                    ]);
            });
            
            $response = $this->postJson('/api/v1/auth/google', [
                'id_token' => $idToken,
            ]);
            
            $afterAuth = now();
            
            $response->assertStatus(200);
            
            // Verify existing user was linked and email_verified_at was set
            $existingUser->refresh();
            $this->assertEquals($googleId, $existingUser->google_id);
            $this->assertNotNull($existingUser->email_verified_at);
            
            // Verify email_verified_at is within reasonable time range (within 5 seconds)
            $this->assertTrue(
                $existingUser->email_verified_at->between($beforeAuth->subSeconds(1), $afterAuth->addSeconds(1)),
                'email_verified_at should be set to current timestamp when linking existing user'
            );
        }
    }
}
