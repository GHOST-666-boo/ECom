<?php

namespace Tests\Unit\Events;

use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_placed_event_stores_order(): void
    {
        $order = Order::factory()->create();
        $event = new OrderPlaced($order);

        $this->assertSame($order, $event->order);
    }

    public function test_order_shipped_event_stores_order(): void
    {
        $order = Order::factory()->create();
        $event = new OrderShipped($order);

        $this->assertSame($order, $event->order);
    }

    public function test_order_delivered_event_stores_order(): void
    {
        $order = Order::factory()->create();
        $event = new OrderDelivered($order);

        $this->assertSame($order, $event->order);
    }

    public function test_order_cancelled_event_stores_order_and_default_reason(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCancelled($order);

        $this->assertSame($order, $event->order);
        $this->assertEquals('customer request', $event->reason);
    }

    public function test_order_cancelled_event_stores_custom_reason(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCancelled($order, 'payment timeout');

        $this->assertEquals('payment timeout', $event->reason);
    }
}
