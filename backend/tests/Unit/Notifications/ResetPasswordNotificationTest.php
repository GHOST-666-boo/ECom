<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetPasswordNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_mail_channel(): void
    {
        $notification = new ResetPasswordNotification('test-token');

        $user = User::factory()->create();
        $this->assertEquals(['mail'], $notification->via($user));
    }

    public function test_stores_token(): void
    {
        $notification = new ResetPasswordNotification('my-reset-token');

        $this->assertEquals('my-reset-token', $notification->token);
    }

    public function test_to_mail_contains_reset_url_with_token(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = new ResetPasswordNotification('abc123');

        $mail = $notification->toMail($user);

        $this->assertEquals('Reset Password Notification', $mail->subject);
        $this->assertNotEmpty($mail->actionUrl);
        $this->assertStringContainsString('abc123', $mail->actionUrl);
        $this->assertStringContainsString(urlencode('user@example.com'), $mail->actionUrl);
    }

    public function test_to_array_returns_empty(): void
    {
        $notification = new ResetPasswordNotification('token');
        $user = User::factory()->create();

        $this->assertEquals([], $notification->toArray($user));
    }
}
