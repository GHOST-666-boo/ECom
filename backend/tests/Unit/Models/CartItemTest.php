<?php

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_item_has_fillable_attributes(): void
    {
        $cartItem = new CartItem;

        $this->assertEquals(['cart_id', 'product_id', 'quantity'], $cartItem->getFillable());
    }

    public function test_cart_item_casts_quantity_to_integer(): void
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => '3',
        ]);

        $this->assertIsInt($cartItem->quantity);
        $this->assertEquals(3, $cartItem->quantity);
    }

    public function test_cart_item_belongs_to_cart(): void
    {
        $cart = Cart::factory()->create();
        $cartItem = CartItem::factory()->create(['cart_id' => $cart->id]);

        $this->assertInstanceOf(Cart::class, $cartItem->cart);
        $this->assertEquals($cart->id, $cartItem->cart->id);
    }

    public function test_cart_item_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $cartItem->product);
        $this->assertEquals($product->id, $cartItem->product->id);
    }
}
