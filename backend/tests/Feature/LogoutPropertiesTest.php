<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LogoutPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 10: Logout Revokes Only Current Token
     * 
     * For any logout request, only the current Sanctum token should be revoked,
     * leaving other tokens for the same user active.
     * 
     * **Validates: Requirements 1.13**
     */
    public function test_property_10_logout_revokes_only_current_token(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create a user with verified email
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);
            
            // Create multiple tokens for the same user (simulating multiple devices)
            // Random number of tokens between 2 and 5
            $tokenCount = rand(2, 5);
            $tokens = [];
            
            for ($j = 0; $j < $tokenCount; $j++) {
                $token = $user->createToken(
                    "device_{$j}",
                    ['*'],
                    now()->addDays(7)
                );
                $tokens[] = [
                    'plain' => $token->plainTextToken,
                    'model' => $token->accessToken,
                    'id' => $token->accessToken->id,
                ];
            }
            
            // Verify all tokens exist in the database for THIS user
            $userTokensBefore = PersonalAccessToken::where('tokenable_id', $user->id)->count();
            $this->assertEquals(
                $tokenCount,
                $userTokensBefore,
                "Expected {$tokenCount} tokens to exist before logout for user {$user->id}"
            );
            
            // Pick a random token to use for logout
            $logoutTokenIndex = rand(0, $tokenCount - 1);
            $logoutToken = $tokens[$logoutTokenIndex]['plain'];
            $logoutTokenId = $tokens[$logoutTokenIndex]['id'];
            
            // Perform logout with the selected token
            // Clear any cached authentication state to ensure Sanctum properly identifies the current token
            $this->app->forgetInstance('auth');
            
            $response = $this->withHeader('Authorization', 'Bearer ' . $logoutToken)
                ->postJson('/api/v1/auth/logout');
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
            
            // Verify only the current token was revoked for THIS user
            $userTokensAfter = PersonalAccessToken::where('tokenable_id', $user->id)->count();
            
            $this->assertEquals(
                $tokenCount - 1,
                $userTokensAfter,
                "Expected " . ($tokenCount - 1) . " tokens to remain after logout for user {$user->id}, but found {$userTokensAfter}"
            );
            
            // Verify the logged-out token is no longer in the database
            $deletedToken = PersonalAccessToken::find($logoutTokenId);
            $this->assertNull(
                $deletedToken,
                "The logged-out token (ID: {$logoutTokenId}) should be deleted from the database"
            );
            
            // Verify all other tokens for THIS user are still active and can be used
            foreach ($tokens as $index => $tokenData) {
                if ($index === $logoutTokenIndex) {
                    continue; // Skip the logged-out token
                }
                
                $tokenId = $tokenData['id'];
                $token = PersonalAccessToken::find($tokenId);
                
                $this->assertNotNull(
                    $token,
                    "Token at index {$index} (ID: {$tokenId}) should still exist in the database"
                );
                
                // Verify the other tokens can still be used to access protected endpoints
                $response = $this->withHeader('Authorization', 'Bearer ' . $tokenData['plain'])
                    ->getJson('/api/v1/auth/user');
                
                $response->assertStatus(200);
                $response->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                        ],
                    ],
                ]);
            }
        }
    }
}
