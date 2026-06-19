<?php

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_has_fillable_attributes(): void
    {
        $cart = new Cart;

        $this->assertEquals(['user_id'], $cart->getFillable());
    }

    public function test_cart_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $cart->user);
        $this->assertEquals($user->id, $cart->user->id);
    }

    public function test_cart_has_many_cart_items(): void
    {
        $cart = Cart::factory()->create();

        $this->assertCount(0, $cart->cartItems);
    }
}
