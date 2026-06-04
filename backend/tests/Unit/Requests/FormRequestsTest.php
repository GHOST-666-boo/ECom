<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\GoogleAuthRequest;
use App\Http\Requests\NewsletterSubscribeRequest;
use App\Http\Requests\PasswordResetEmailRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\RegisterRequest;
use Tests\TestCase;

class FormRequestsTest extends TestCase
{
    public function test_register_request_is_authorized(): void
    {
        $request = new RegisterRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_register_request_rules(): void
    {
        $request = new RegisterRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('email', $rules['email']);
        $this->assertStringContainsString('unique:users', $rules['email']);
        $this->assertStringContainsString('confirmed', $rules['password']);
        $this->assertStringContainsString('min:8', $rules['password']);
    }

    public function test_google_auth_request_is_authorized(): void
    {
        $request = new GoogleAuthRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_google_auth_request_rules(): void
    {
        $request = new GoogleAuthRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('id_token', $rules);
        $this->assertStringContainsString('required', $rules['id_token']);
        $this->assertStringContainsString('string', $rules['id_token']);
    }

    public function test_google_auth_request_custom_messages(): void
    {
        $request = new GoogleAuthRequest;
        $messages = $request->messages();

        $this->assertArrayHasKey('id_token.required', $messages);
        $this->assertArrayHasKey('id_token.string', $messages);
    }

    public function test_newsletter_subscribe_request_is_authorized(): void
    {
        $request = new NewsletterSubscribeRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_newsletter_subscribe_request_rules(): void
    {
        $request = new NewsletterSubscribeRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('max:255', $rules['email']);
    }

    public function test_newsletter_subscribe_request_custom_messages(): void
    {
        $request = new NewsletterSubscribeRequest;
        $messages = $request->messages();

        $this->assertArrayHasKey('email.required', $messages);
        $this->assertArrayHasKey('email.email', $messages);
        $this->assertArrayHasKey('email.max', $messages);
    }

    public function test_password_reset_request_is_authorized(): void
    {
        $request = new PasswordResetRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_password_reset_request_rules(): void
    {
        $request = new PasswordResetRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('token', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertStringContainsString('required', $rules['token']);
        $this->assertStringContainsString('exists:users,email', $rules['email']);
    }

    public function test_password_reset_request_custom_messages(): void
    {
        $request = new PasswordResetRequest;
        $messages = $request->messages();

        $this->assertArrayHasKey('email.exists', $messages);
    }

    public function test_password_reset_email_request_is_authorized(): void
    {
        $request = new PasswordResetEmailRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_password_reset_email_request_rules(): void
    {
        $request = new PasswordResetEmailRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertStringContainsString('required', $rules['email']);
        $this->assertStringContainsString('email', $rules['email']);
        $this->assertStringContainsString('exists:users,email', $rules['email']);
    }

    public function test_password_reset_email_request_custom_messages(): void
    {
        $request = new PasswordResetEmailRequest;
        $messages = $request->messages();

        $this->assertArrayHasKey('email.exists', $messages);
    }
}
