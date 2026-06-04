<?php

namespace Tests\Unit\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFailedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_mail_channel(): void
    {
        $order = Order::factory()->create();
        $notification = new PaymentFailedNotification($order, [], []);

        $this->assertEquals(['mail'], $notification->via($order->user));
    }

    public function test_to_array_includes_order_and_item_data(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-FAIL-001']);
        $restored = [['name' => 'Widget', 'quantity' => 2]];
        $skipped = [['name' => 'Gadget', 'quantity' => 1]];

        $notification = new PaymentFailedNotification($order, $restored, $skipped);
        $array = $notification->toArray($order->user);

        $this->assertEquals($order->id, $array['order_id']);
        $this->assertEquals('ORD-FAIL-001', $array['order_number']);
        $this->assertEquals($restored, $array['restored_items']);
        $this->assertEquals($skipped, $array['skipped_items']);
    }

    public function test_to_mail_contains_restored_items(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-FAIL-002',
        ]);
        $restored = [['name' => 'Widget', 'quantity' => 2]];

        $notification = new PaymentFailedNotification($order, $restored, []);
        $mail = $notification->toMail($user);

        $this->assertEquals('Payment Failed - Order ORD-FAIL-002', $mail->subject);
    }

    public function test_to_mail_contains_skipped_items(): void
    {
        $user = User::factory()->create(['name' => 'Bob']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-FAIL-003',
        ]);
        $skipped = [['name' => 'Gadget', 'quantity' => 1]];

        $notification = new PaymentFailedNotification($order, [], $skipped);
        $mail = $notification->toMail($user);

        $this->assertEquals('Payment Failed - Order ORD-FAIL-003', $mail->subject);
    }
}
