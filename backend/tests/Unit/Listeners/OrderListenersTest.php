<?php

namespace Tests\Unit\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Listeners\SendOrderCancelledNotification;
use App\Listeners\SendOrderDeliveredNotification;
use App\Listeners\SendOrderPlacedNotification;
use App\Listeners\SendOrderShippedNotification;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderListenersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->order = Order::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_send_order_placed_notification_listener(): void
    {
        Notification::fake();

        $event = new OrderPlaced($this->order);
        $listener = new SendOrderPlacedNotification;
        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            OrderPlacedNotification::class
        );
    }

    public function test_send_order_cancelled_notification_listener(): void
    {
        Notification::fake();

        $event = new OrderCancelled($this->order, 'payment timeout');
        $listener = new SendOrderCancelledNotification;
        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            OrderCancelledNotification::class
        );
    }

    public function test_send_order_shipped_notification_listener(): void
    {
        Notification::fake();

        $event = new OrderShipped($this->order);
        $listener = new SendOrderShippedNotification;
        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            OrderShippedNotification::class
        );
    }

    public function test_send_order_delivered_notification_listener(): void
    {
        Notification::fake();

        $event = new OrderDelivered($this->order);
        $listener = new SendOrderDeliveredNotification;
        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            OrderDeliveredNotification::class
        );
    }

    public function test_listeners_have_retry_configuration(): void
    {
        $placed = new SendOrderPlacedNotification;
        $cancelled = new SendOrderCancelledNotification;
        $shipped = new SendOrderShippedNotification;
        $delivered = new SendOrderDeliveredNotification;

        $this->assertEquals(3, $placed->tries);
        $this->assertEquals(3, $cancelled->tries);
        $this->assertEquals(3, $shipped->tries);
        $this->assertEquals(3, $delivered->tries);

        $this->assertEquals([60, 300, 900], $placed->backoff);
        $this->assertEquals([60, 300, 900], $cancelled->backoff);
        $this->assertEquals([60, 300, 900], $shipped->backoff);
        $this->assertEquals([60, 300, 900], $delivered->backoff);
    }
}
