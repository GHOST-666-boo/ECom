<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\FilamentAdminAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FilamentAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private FilamentAdminAuth $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new FilamentAdminAuth;
    }

    public function test_admin_user_can_proceed(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $request = Request::create('/admin', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_non_admin_user_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        $request = Request::create('/admin', 'GET');

        $this->expectException(HttpException::class);

        $this->middleware->handle($request, function () {
            return new Response('OK');
        });
    }

    public function test_unauthenticated_request_proceeds(): void
    {
        $request = Request::create('/admin', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
