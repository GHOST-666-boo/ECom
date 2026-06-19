<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private SecurityHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeaders;
    }

    public function test_sets_x_content_type_options_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_sets_x_frame_options_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_sets_referrer_policy_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals('same-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_sets_hsts_header_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security')
        );
    }

    public function test_does_not_set_hsts_header_in_non_production(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_removes_x_powered_by_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            $resp = new Response('OK');
            $resp->headers->set('X-Powered-By', 'PHP/8.4');

            return $resp;
        });

        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_removes_server_header(): void
    {
        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            $resp = new Response('OK');
            $resp->headers->set('Server', 'Apache');

            return $resp;
        });

        $this->assertNull($response->headers->get('Server'));
    }
}
