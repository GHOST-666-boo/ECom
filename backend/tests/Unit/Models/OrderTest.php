<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_has_fillable_attributes(): void
    {
        $order = new Order;

        $this->assertEquals([
            'user_id',
            'order_number',
            'status',
            'payment_method',
            'payment_id',
            'payment_status',
            'tracking_number',
            'courier_name',
            'total',
            'address_snapshot',
        ], $order->getFillable());
    }

    public function test_order_casts_total_to_decimal(): void
    {
        $order = Order::factory()->create(['total' => 199.99]);

        $this->assertEquals('199.99', $order->total);
    }

    public function test_order_casts_address_snapshot_to_array(): void
    {
        $snapshot = ['name' => 'John', 'city' => 'Mumbai'];
        $order = Order::factory()->create(['address_snapshot' => $snapshot]);

        $this->assertIsArray($order->address_snapshot);
        $this->assertEquals('John', $order->address_snapshot['name']);
    }

    public function test_is_shipped_returns_true_for_shipped_status(): void
    {
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->assertTrue($order->isShipped());
    }

    public function test_is_shipped_returns_true_for_delivered_status(): void
    {
        $order = Order::factory()->create(['status' => 'delivered']);

        $this->assertTrue($order->isShipped());
    }

    public function test_is_shipped_returns_false_for_pending_status(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->assertFalse($order->isShipped());
    }

    public function test_is_shipped_returns_false_for_confirmed_status(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $this->assertFalse($order->isShipped());
    }

    public function test_is_shipped_returns_false_for_cancelled_status(): void
    {
        $order = Order::factory()->create(['status' => 'cancelled']);

        $this->assertFalse($order->isShipped());
    }

    public function test_is_cod_paid_returns_true_for_cod_with_paid_status(): void
    {
        $order = Order::factory()->create([
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);

        $this->assertTrue($order->isCodPaid());
    }

    public function test_is_cod_paid_returns_false_for_cod_with_unpaid_status(): void
    {
        $order = Order::factory()->create([
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $this->assertFalse($order->isCodPaid());
    }

    public function test_is_cod_paid_returns_false_for_razorpay_with_paid_status(): void
    {
        $order = Order::factory()->create([
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
        ]);

        $this->assertFalse($order->isCodPaid());
    }

    public function test_order_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_has_many_order_items(): void
    {
        $order = Order::factory()->create();

        $this->assertCount(0, $order->orderItems);
    }
}
