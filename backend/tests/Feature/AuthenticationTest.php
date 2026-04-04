<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify your account.',
                'data' => null,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'customer',
        ]);

        // Verify email_verified_at is null
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_logout_revokes_only_current_token()
    {
        $user = User::factory()->create();
        
        // Create two tokens for the same user (simulating multi-device login)
        $tokenObject1 = $user->createToken('device1');
        $token1 = $tokenObject1->plainTextToken;
        $token1Id = $tokenObject1->accessToken->id;
        
        $tokenObject2 = $user->createToken('device2');
        $token2 = $tokenObject2->plainTextToken;
        $token2Id = $tokenObject2->accessToken->id;

        // Verify both tokens exist
        $this->assertDatabaseCount('personal_access_tokens', 2);

        // Logout using token1
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Verify only one token remains (token2 should still be active)
        $this->assertDatabaseCount('personal_access_tokens', 1);
        
        // Verify token1 was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token1Id,
        ]);
        
        // Verify token2 still exists
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token2Id,
        ]);

        // Verify token2 is still valid by making an authenticated request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    public function test_token_expires_after_seven_days()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token');

        // Check that token expiration is set to 7 days (10080 minutes)
        $this->assertEquals(10080, config('sanctum.expiration'));
    }

    public function test_registration_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_validates_email_format()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validates_unique_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validates_password_minimum_length()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_validates_password_confirmation()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_validates_name_max_length()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_password_is_hashed_with_bcrypt()
    {
        // Set bcrypt rounds to 12 for this test
        config(['hashing.bcrypt.rounds' => 12]);
        
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        // Verify password is hashed
        $this->assertNotEquals('password123', $user->password);
        
        // Verify bcrypt is used (bcrypt hashes start with $2y$)
        $this->assertStringStartsWith('$2y$', $user->password);
        
        // Verify bcrypt cost factor is 12 (format: $2y$12$...)
        $this->assertStringStartsWith('$2y$12$', $user->password);
    }

    public function test_email_verification_notification_is_sent()
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        // Verify email verification notification was sent
        Notification::assertSentTo(
            $user,
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }

    public function test_login_rate_limiting_enforces_five_attempts_per_minute()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Make 5 failed login attempts (should succeed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
            
            $response->assertStatus(401);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_login_returns_token_with_user_data()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email', 'name'],
                    'token',
                ],
            ]);

        // Verify token is a valid string
        $this->assertIsString($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_authenticated_user_can_get_user_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+91 9876543210',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'phone' => '+91 9876543210',
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_update_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+91 9876543210',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/user/profile', [
                'name' => 'Jane Doe',
                'phone' => '+91 9999999999',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Jane Doe',
                        'email' => 'john@example.com', // Email should not change
                        'phone' => '+91 9999999999',
                    ],
                ],
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'phone' => '+91 9999999999',
        ]);
    }

    public function test_profile_update_validates_required_name()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/user/profile', [
                'name' => '',
                'phone' => '+91 9999999999',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_profile_update_validates_name_max_length()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/user/profile', [
                'name' => str_repeat('a', 256),
                'phone' => '+91 9999999999',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_profile_update_validates_phone_max_length()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/user/profile', [
                'name' => 'John Doe',
                'phone' => str_repeat('1', 21),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_profile_update_allows_null_phone()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'phone' => '+91 9876543210',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/user/profile', [
                'name' => 'John Doe',
                'phone' => null,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);

        // Verify phone was set to null
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => null,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_profile()
    {
        $response = $this->getJson('/api/v1/user/profile');
        $response->assertStatus(401);

        $response = $this->putJson('/api/v1/user/profile', [
            'name' => 'John Doe',
            'phone' => '+91 9999999999',
        ]);
        $response->assertStatus(401);
    }
}
