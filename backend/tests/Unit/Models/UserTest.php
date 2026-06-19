<?php

namespace Tests\Unit\Models;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_casts_email_verified_at_to_datetime(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
    }

    public function test_user_casts_password_as_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plaintext']);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(Hash::check('plaintext', $user->password));
    }

    public function test_user_has_one_cart(): void
    {
        $user = User::factory()->create();
        Cart::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Cart::class, $user->cart);
    }

    public function test_user_has_many_addresses(): void
    {
        $user = User::factory()->create();
        Address::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->addresses);
    }

    public function test_user_has_many_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->orders);
    }

    public function test_send_password_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->sendPasswordResetNotification('test-token');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            return $notification->token === 'test-token';
        });
    }

    public function test_admin_user_can_access_panel(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $panel = $this->createMock(Panel::class);
        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_customer_user_cannot_access_panel(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $panel = $this->createMock(Panel::class);
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_default_customer_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $panel = $this->createMock(Panel::class);
        $this->assertFalse($user->canAccessPanel($panel));
    }
}
