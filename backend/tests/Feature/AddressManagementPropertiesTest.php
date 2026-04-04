<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressManagementPropertiesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 84: Address Creation Requires Fields
     * 
     * For any address creation request missing name, line1, city, state, or pincode,
     * the request should fail with a validation error.
     * 
     * **Validates: Requirements 13.2**
     */
    public function test_property_84_address_creation_requires_fields(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            
            // Test each required field by omitting it
            $requiredFields = ['name', 'line1', 'city', 'state', 'pincode'];
            $fieldToOmit = $requiredFields[array_rand($requiredFields)];
            
            $addressData = [
                'name' => fake()->name(),
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->randomElement(['Maharashtra', 'Karnataka', 'Tamil Nadu', 'Delhi', 'Gujarat']),
                'pincode' => fake()->numerify('######'),
            ];
            
            // Remove the randomly selected required field
            unset($addressData[$fieldToOmit]);
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/user/addresses', $addressData);
            
            $response->assertStatus(422);
            $response->assertJsonValidationErrors([$fieldToOmit]);
        }
    }

    /**
     * Property 85: Only One Default Address Per User
     * 
     * For any user, there should be at most one address with is_default = true
     * at any given time.
     * 
     * **Validates: Requirements 13.4**
     */
    public function test_property_85_only_one_default_address_per_user(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            
            // Create multiple addresses for the user
            $addressCount = rand(2, 5);
            $addresses = [];
            
            for ($j = 0; $j < $addressCount; $j++) {
                $address = Address::factory()->create([
                    'user_id' => $user->id,
                    'is_default' => false,
                ]);
                $addresses[] = $address;
            }
            
            // Randomly select one address to mark as default
            $selectedAddress = $addresses[array_rand($addresses)];
            
            $response = $this->actingAs($user)
                ->putJson("/api/v1/user/addresses/{$selectedAddress->id}/default");
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Default address updated successfully',
            ]);
            
            // Verify only one address is marked as default
            $defaultCount = Address::where('user_id', $user->id)
                ->where('is_default', true)
                ->count();
            
            $this->assertEquals(1, $defaultCount, "User should have exactly one default address, found {$defaultCount}");
            
            // Verify the correct address is marked as default
            $selectedAddress->refresh();
            $this->assertTrue($selectedAddress->is_default);
            
            // Verify all other addresses are not default
            foreach ($addresses as $address) {
                if ($address->id !== $selectedAddress->id) {
                    $address->refresh();
                    $this->assertFalse($address->is_default, "Address {$address->id} should not be default");
                }
            }
        }
    }

    /**
     * Property 86: Address List Filtered by User
     * 
     * For any address list request, all returned addresses should belong to
     * the authenticated user.
     * 
     * **Validates: Requirements 13.7**
     */
    public function test_property_86_address_list_filtered_by_user(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create multiple users with addresses
            $userCount = rand(2, 5);
            $users = [];
            
            for ($j = 0; $j < $userCount; $j++) {
                $user = User::factory()->create();
                $users[] = $user;
                
                // Create random number of addresses for each user
                $addressCount = rand(1, 4);
                Address::factory()->count($addressCount)->create([
                    'user_id' => $user->id,
                ]);
            }
            
            // Select a random user to test
            $testUser = $users[array_rand($users)];
            
            // Get addresses for the test user
            $response = $this->actingAs($testUser)
                ->getJson('/api/v1/user/addresses');
            
            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'message' => 'Addresses retrieved successfully',
            ]);
            
            $addresses = $response->json('data.addresses');
            
            // Verify all returned addresses belong to the authenticated user
            foreach ($addresses as $address) {
                $dbAddress = Address::find($address['id']);
                $this->assertNotNull($dbAddress);
                $this->assertEquals($testUser->id, $dbAddress->user_id, 
                    "Address {$address['id']} should belong to user {$testUser->id}, but belongs to user {$dbAddress->user_id}");
            }
            
            // Verify the count matches database
            $expectedCount = Address::where('user_id', $testUser->id)->count();
            $this->assertCount($expectedCount, $addresses, 
                "Expected {$expectedCount} addresses for user {$testUser->id}, got " . count($addresses));
        }
    }

    /**
     * Property 87: Pincode Format Validation
     * 
     * For any address creation or update with invalid pincode format (not 6 digits),
     * the request should fail with a validation error.
     * 
     * **Validates: Requirements 13.8**
     */
    public function test_property_87_pincode_format_validation(): void
    {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create();
            
            // Generate invalid pincodes - ensure they don't match the 6-digit pattern
            $invalidPincodes = [
                fake()->numerify('#####'),      // 5 digits
                fake()->numerify('#######'),    // 7 digits
                fake()->numerify('####'),       // 4 digits
                fake()->lexify('??????'),       // 6 letters
                fake()->bothify('###A##'),      // Contains letter in middle
                'ABCDEF',                       // All letters
                '12-3456',                      // Contains hyphen
                '123 456',                      // Contains space
                '12345',                        // 5 digits
                '1234567',                      // 7 digits
                '',                             // Empty string
                'abc123',                       // Mixed letters and numbers
            ];
            
            $invalidPincode = $invalidPincodes[array_rand($invalidPincodes)];
            
            // Test address creation with invalid pincode
            $addressData = [
                'name' => fake()->name(),
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->randomElement(['Maharashtra', 'Karnataka', 'Tamil Nadu', 'Delhi', 'Gujarat']),
                'pincode' => $invalidPincode,
            ];
            
            $response = $this->actingAs($user)
                ->postJson('/api/v1/user/addresses', $addressData);
            
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['pincode']);
            
            // Also test address update with invalid pincode
            $validAddress = Address::factory()->create([
                'user_id' => $user->id,
                'pincode' => '123456', // Valid pincode
            ]);
            
            $updateData = [
                'name' => fake()->name(),
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->randomElement(['Maharashtra', 'Karnataka', 'Tamil Nadu', 'Delhi', 'Gujarat']),
                'pincode' => $invalidPincode,
            ];
            
            $response = $this->actingAs($user)
                ->putJson("/api/v1/user/addresses/{$validAddress->id}", $updateData);
            
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['pincode']);
            
            // Verify the address was not updated
            $validAddress->refresh();
            $this->assertEquals('123456', $validAddress->pincode);
        }
    }
}
