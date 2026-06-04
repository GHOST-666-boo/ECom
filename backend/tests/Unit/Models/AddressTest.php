<?php

namespace Tests\Unit\Models;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_has_fillable_attributes(): void
    {
        $address = new Address;

        $this->assertEquals([
            'user_id',
            'name',
            'line1',
            'line2',
            'city',
            'state',
            'pincode',
            'is_default',
        ], $address->getFillable());
    }

    public function test_address_casts_is_default_to_boolean(): void
    {
        $address = Address::factory()->create(['is_default' => 1]);

        $this->assertIsBool($address->is_default);
        $this->assertTrue($address->is_default);
    }

    public function test_address_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $address->user);
        $this->assertEquals($user->id, $address->user->id);
    }
}
