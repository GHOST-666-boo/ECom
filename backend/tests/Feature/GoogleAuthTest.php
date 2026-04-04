<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_auth_requires_id_token()
    {
        $response = $this->postJson('/api/v1/auth/google', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_token']);
    }

    public function test_google_auth_validates_id_token_is_string()
    {
        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 123,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_token']);
    }

    public function test_google_auth_rejects_invalid_token()
    {
        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'invalid-token-string',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Google authentication failed.',
            ]);
    }

    public function test_google_auth_creates_new_user_with_verified_email()
    {
        // Mock GoogleAuthService
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->with('valid-google-token')
                ->andReturn([
                    'sub' => 'google-user-123',
                    'email' => 'newuser@gmail.com',
                    'name' => 'New User',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-google-token',
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

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@gmail.com',
            'google_id' => 'google-user-123',
            'role' => 'customer',
        ]);

        // Verify email_verified_at is set
        $user = User::where('email', 'newuser@gmail.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_auth_links_existing_user_by_email()
    {
        // Create existing user without google_id
        $existingUser = User::factory()->create([
            'email' => 'existing@gmail.com',
            'google_id' => null,
            'email_verified_at' => null,
        ]);

        // Mock GoogleAuthService
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->with('valid-google-token')
                ->andReturn([
                    'sub' => 'google-user-456',
                    'email' => 'existing@gmail.com',
                    'name' => 'Existing User',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-google-token',
        ]);

        $response->assertStatus(200);

        // Verify user was linked to Google account
        $existingUser->refresh();
        $this->assertEquals('google-user-456', $existingUser->google_id);
        $this->assertNotNull($existingUser->email_verified_at);
    }

    public function test_google_auth_retrieves_existing_google_user()
    {
        // Create user with google_id
        $existingUser = User::factory()->create([
            'email' => 'google@gmail.com',
            'google_id' => 'google-user-789',
            'email_verified_at' => now(),
        ]);

        // Mock GoogleAuthService
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->with('valid-google-token')
                ->andReturn([
                    'sub' => 'google-user-789',
                    'email' => 'google@gmail.com',
                    'name' => 'Google User',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-google-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $existingUser->id,
                        'email' => 'google@gmail.com',
                    ],
                ],
            ]);

        // Verify no duplicate user was created
        $this->assertEquals(1, User::where('google_id', 'google-user-789')->count());
    }

    public function test_google_auth_returns_sanctum_token()
    {
        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->with('valid-google-token')
                ->andReturn([
                    'sub' => 'google-user-999',
                    'email' => 'token@gmail.com',
                    'name' => 'Token User',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-google-token',
        ]);

        $response->assertStatus(200);

        $token = $response->json('data.token');
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verify token works for authenticated requests
        $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/user');

        $userResponse->assertStatus(200);
    }

    public function test_google_auth_sets_email_verified_for_unverified_existing_user()
    {
        // Create existing Google user without email_verified_at
        $existingUser = User::factory()->create([
            'email' => 'unverified@gmail.com',
            'google_id' => 'google-user-111',
            'email_verified_at' => null,
        ]);

        $this->mock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('verifyIdToken')
                ->once()
                ->with('valid-google-token')
                ->andReturn([
                    'sub' => 'google-user-111',
                    'email' => 'unverified@gmail.com',
                    'name' => 'Unverified User',
                ]);
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-google-token',
        ]);

        $response->assertStatus(200);

        // Verify email_verified_at is now set
        $existingUser->refresh();
        $this->assertNotNull($existingUser->email_verified_at);
    }
}
