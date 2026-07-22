<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\AuthMiddleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Middlewares\AuthMiddleware\ApiAuthMiddleware;

class ApiAuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function it_calls_next_when_user_is_authenticated(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new HttpRequest(url: '/dashboard', method: 'GET');
        $expectedResponse = new HttpResponse(null, statusCode: 200);
        $middleware = new ApiAuthMiddleware();

        $nextCalled = false;
        $next = function (HttpRequest $req) use (&$nextCalled, $expectedResponse) {
            $nextCalled = true;
            return $expectedResponse;
        };

        $response = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertSame($expectedResponse, $response);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }

    #[Test]
    public function it_returns_unauthenticated_response_when_user_is_not_authenticated(): void
    {
        $request = new HttpRequest(url: '/protected-page', method: 'GET');
        $middleware = new ApiAuthMiddleware();

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame(401, $response->statusCode);
        $this->assertNull($response->headers['Location'] ?? null);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }

    #[Test]
    public function it_returns_custom_unauthenticated_response_when_provided(): void
    {
        $request = new HttpRequest(url: '/admin/settings', method: 'GET');
        $middleware = new ApiAuthMiddleware();

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, responseForUnauthenticatedUser: ['error' => 'hehe']);

        $this->assertSame(401, $response->statusCode);
        $this->assertNull($response->headers['Location'] ?? null);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
        $this->assertSame(['error' => 'hehe'], $response->data);
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $request = new HttpRequest(url: '/secret', method: 'GET');
        $customResponse = new HttpResponse(null, statusCode: 401);

        $customHandler = function (HttpRequest $req) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new ApiAuthMiddleware(customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame($customResponse, $response);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $request = new HttpRequest(url: '/secret', method: 'GET');

        $expectedResponse = new HttpResponse(data: ['hehe' => 'hihi']);

        $customHandler = function (HttpRequest $req, $responseForUnauthenticatedUser) use ($request) {
            $this->assertSame($request, $req);
            return new HttpResponse(data: $responseForUnauthenticatedUser);
        };

        $middleware = new ApiAuthMiddleware(customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, ['hehe' => 'hihi']);

        $this->assertEquals($expectedResponse, $response);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }
}