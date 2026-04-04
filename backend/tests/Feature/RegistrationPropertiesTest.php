<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 1: Password Hashing with Bcrypt Cost 12
     * 
     * For any user registration with email and password, the stored password hash
     * should be a valid bcrypt hash with cost factor 12.
     * 
     * **Validates: Requirements 1.2**
     */
    public function test_property_1_password_hashing_with_bcrypt_cost_12(): void
    {
        // Set bcrypt rounds to 12
        config(['hashing.bcrypt.rounds' => 12]);
        
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate random password (8-20 characters)
            $password = fake()->password(8, 20);
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
            ]);
            
            $response->assertStatus(201);
            
            $user = User::where('email', $email)->first();
            
            // Verify password is hashed (not plain text)
            $this->assertNotEquals($password, $user->password);
            
            // Verify bcrypt is used (bcrypt hashes start with $2y$)
            $this->assertStringStartsWith('$2y$', $user->password);
            
            // Verify bcrypt cost factor is 12 (format: $2y$12$...)
            $this->assertStringStartsWith('$2y$12$', $user->password);
            
            // Verify the hash can be verified with the original password
            $this->assertTrue(Hash::check($password, $user->password));
        }
    }

    /**
     * Property 2: Email Verification Link Sent on Registration
     * 
     * For any user registration with email and password, an email verification link
     * should be queued or sent to the user's email address.
     * 
     * **Validates: Requirements 1.3**
     */
    public function test_property_2_email_verification_link_sent_on_registration(): void
    {
        Notification::fake();
        
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            $password = fake()->password(8, 20);
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
            ]);
            
            $response->assertStatus(201);
            
            $user = User::where('email', $email)->first();
            
            // Verify email verification notification was sent
            Notification::assertSentTo(
                $user,
                \Illuminate\Auth\Notifications\VerifyEmail::class
            );
        }
    }

    /**
     * Property 3: Initial Email Verification Status
     * 
     * For any new user registration, the email_verified_at field should be null
     * until the verification link is clicked.
     * 
     * **Validates: Requirements 1.4**
     */
    public function test_property_3_initial_email_verification_status(): void
    {
        $iterations = 15;
        
        for ($i = 0; $i < $iterations; $i++) {
            $password = fake()->password(8, 20);
            $email = fake()->unique()->safeEmail();
            $name = fake()->name();
            
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
            ]);
            
            $response->assertStatus(201);
            
            $user = User::where('email', $email)->first();
            
            // Verify email_verified_at is null for newly registered users
            $this->assertNull($user->email_verified_at);
            
            // Verify user exists in database
            $this->assertDatabaseHas('users', [
                'email' => $email,
                'name' => $name,
                'role' => 'customer',
            ]);
        }
    }
}
