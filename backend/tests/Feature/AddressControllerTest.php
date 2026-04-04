<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_addresses(): void
    {
        $response = $this->getJson('/api/v1/user/addresses');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_retrieve_empty_address_list(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/addresses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Addresses retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'addresses',
                ],
            ]);

        $this->assertCount(0, $response->json('data.addresses'));
    }

    public function test_authenticated_user_can_create_address(): void
    {
        $addressData = [
            'name' => 'John Doe',
            'line1' => '123 Main Street',
            'line2' => 'Apt 4B',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Address created successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'address' => [
                        'id',
                        'name',
                        'line1',
                        'line2',
                        'city',
                        'state',
                        'pincode',
                        'is_default',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'line1' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
        ]);
    }

    public function test_address_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/addresses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'line1', 'city', 'state', 'pincode']);
    }

    public function test_address_creation_validates_pincode_format(): void
    {
        $addressData = [
            'name' => 'John Doe',
            'line1' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '12345', // Invalid: only 5 digits
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pincode']);

        // Test with non-numeric pincode
        $addressData['pincode'] = 'ABCDEF';
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pincode']);

        // Test with 7 digits
        $addressData['pincode'] = '1234567';
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pincode']);
    }

    public function test_authenticated_user_can_update_address(): void
    {
        $address = Address::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $updatedData = [
            'name' => 'Jane Doe',
            'line1' => '456 Oak Avenue',
            'line2' => 'Suite 10',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/user/addresses/{$address->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Address updated successfully',
            ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $this->user->id,
            'name' => 'Jane Doe',
            'line1' => '456 Oak Avenue',
            'city' => 'Delhi',
            'pincode' => '110001',
        ]);
    }

    public function test_user_cannot_update_another_users_address(): void
    {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $updatedData = [
            'name' => 'Hacker',
            'line1' => 'Malicious Street',
            'city' => 'Hackerville',
            'state' => 'Hack',
            'pincode' => '999999',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/user/addresses/{$address->id}", $updatedData);

        $response->assertStatus(404);

        // Verify the address was not updated
        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
            'name' => 'Hacker',
        ]);
    }

    public function test_authenticated_user_can_delete_address(): void
    {
        $address = Address::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/user/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Address deleted successfully',
            ]);

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_address(): void
    {
        $otherUser = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/user/addresses/{$address->id}");

        $response->assertStatus(404);

        // Verify the address still exists
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_authenticated_user_can_set_default_address(): void
    {
        // Create two addresses
        $address1 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        $address2 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        // Set address2 as default
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/user/addresses/{$address2->id}/default");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Default address updated successfully',
            ]);

        // Verify address2 is now default
        $this->assertDatabaseHas('addresses', [
            'id' => $address2->id,
            'is_default' => true,
        ]);

        // Verify address1 is no longer default
        $this->assertDatabaseHas('addresses', [
            'id' => $address1->id,
            'is_default' => false,
        ]);
    }

    public function test_only_one_address_can_be_default_per_user(): void
    {
        // Create three addresses
        $address1 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $address2 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $address3 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        // Set address1 as default
        $this->actingAs($this->user)
            ->putJson("/api/v1/user/addresses/{$address1->id}/default");

        // Set address2 as default
        $this->actingAs($this->user)
            ->putJson("/api/v1/user/addresses/{$address2->id}/default");

        // Set address3 as default
        $this->actingAs($this->user)
            ->putJson("/api/v1/user/addresses/{$address3->id}/default");

        // Verify only address3 is default
        $this->assertDatabaseHas('addresses', [
            'id' => $address3->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address1->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address2->id,
            'is_default' => false,
        ]);

        // Verify user has exactly one default address
        $defaultCount = Address::where('user_id', $this->user->id)
            ->where('is_default', true)
            ->count();

        $this->assertEquals(1, $defaultCount);
    }

    public function test_creating_address_with_is_default_true_unmarks_previous_default(): void
    {
        // Create first address as default
        $address1 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        // Create second address with is_default = true
        $addressData = [
            'name' => 'John Doe',
            'line1' => '123 Main Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'is_default' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/addresses', $addressData);

        $response->assertStatus(201);

        // Verify address1 is no longer default
        $this->assertDatabaseHas('addresses', [
            'id' => $address1->id,
            'is_default' => false,
        ]);

        // Verify new address is default
        $newAddressId = $response->json('data.address.id');
        $this->assertDatabaseHas('addresses', [
            'id' => $newAddressId,
            'is_default' => true,
        ]);
    }

    public function test_user_can_retrieve_multiple_addresses(): void
    {
        // Create multiple addresses
        Address::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/addresses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data.addresses'));
    }

    public function test_user_only_sees_their_own_addresses(): void
    {
        // Create addresses for current user
        Address::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        // Create addresses for another user
        $otherUser = User::factory()->create();
        Address::factory()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/addresses');

        $response->assertStatus(200);

        // User should only see their 2 addresses, not the other user's 3
        $this->assertCount(2, $response->json('data.addresses'));
    }

    public function test_addresses_are_ordered_with_default_first(): void
    {
        // Create addresses with different timestamps
        $address1 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subDays(3),
        ]);

        $address2 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
            'created_at' => now()->subDays(2),
        ]);

        $address3 = Address::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/addresses');

        $response->assertStatus(200);

        $addresses = $response->json('data.addresses');

        // Default address should be first
        $this->assertEquals($address2->id, $addresses[0]['id']);
        $this->assertTrue($addresses[0]['is_default']);
    }
}
