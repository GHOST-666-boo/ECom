<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_has_fillable_attributes(): void
    {
        $orderItem = new OrderItem;

        $this->assertEquals(['order_id', 'product_id', 'quantity', 'price'], $orderItem->getFillable());
    }

    public function test_order_item_casts_quantity_to_integer(): void
    {
        $orderItem = OrderItem::factory()->create(['quantity' => '5']);

        $this->assertIsInt($orderItem->quantity);
        $this->assertEquals(5, $orderItem->quantity);
    }

    public function test_order_item_casts_price_to_decimal(): void
    {
        $orderItem = OrderItem::factory()->create(['price' => 49.99]);

        $this->assertEquals('49.99', $orderItem->price);
    }

    public function test_order_item_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($order->id, $orderItem->order->id);
    }

    public function test_order_item_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $orderItem->product);
        $this->assertEquals($product->id, $orderItem->product->id);
    }
}
