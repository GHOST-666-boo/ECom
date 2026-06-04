<?php

namespace Tests\Unit\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-001',
            'total' => 500.00,
        ]);
    }

    public function test_order_placed_notification_uses_mail_channel(): void
    {
        $notification = new OrderPlacedNotification($this->order);

        $this->assertEquals(['mail'], $notification->via($this->user));
    }

    public function test_order_placed_notification_to_array(): void
    {
        $notification = new OrderPlacedNotification($this->order);
        $array = $notification->toArray($this->user);

        $this->assertEquals($this->order->id, $array['order_id']);
        $this->assertEquals('ORD-TEST-001', $array['order_number']);
        $this->assertEquals($this->order->total, $array['total']);
    }

    public function test_order_cancelled_notification_uses_mail_channel(): void
    {
        $notification = new OrderCancelledNotification($this->order, 'customer request');

        $this->assertEquals(['mail'], $notification->via($this->user));
    }

    public function test_order_cancelled_notification_to_array(): void
    {
        $notification = new OrderCancelledNotification($this->order, 'out of stock');
        $array = $notification->toArray($this->user);

        $this->assertEquals($this->order->id, $array['order_id']);
        $this->assertEquals('ORD-TEST-001', $array['order_number']);
        $this->assertEquals('out of stock', $array['reason']);
    }

    public function test_order_cancelled_notification_stores_reason(): void
    {
        $notification = new OrderCancelledNotification($this->order, 'payment timeout');

        $this->assertEquals('payment timeout', $notification->reason);
    }

    public function test_order_shipped_notification_uses_mail_channel(): void
    {
        $notification = new OrderShippedNotification($this->order);

        $this->assertEquals(['mail'], $notification->via($this->user));
    }

    public function test_order_shipped_notification_to_array(): void
    {
        $notification = new OrderShippedNotification($this->order);
        $array = $notification->toArray($this->user);

        $this->assertEquals($this->order->id, $array['order_id']);
        $this->assertEquals('ORD-TEST-001', $array['order_number']);
    }

    public function test_order_delivered_notification_uses_mail_channel(): void
    {
        $notification = new OrderDeliveredNotification($this->order);

        $this->assertEquals(['mail'], $notification->via($this->user));
    }

    public function test_order_delivered_notification_to_array(): void
    {
        $notification = new OrderDeliveredNotification($this->order);
        $array = $notification->toArray($this->user);

        $this->assertEquals($this->order->id, $array['order_id']);
        $this->assertEquals('ORD-TEST-001', $array['order_number']);
    }
}
