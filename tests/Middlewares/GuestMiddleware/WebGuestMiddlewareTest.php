<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\GuestMiddleware;

use PHPUnit\Framework\Attributes\Test;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Middlewares\GuestMiddleware\WebGuestMiddleware;
use PHPUnit\Framework\TestCase;

class WebGuestMiddlewareTest extends TestCase
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
    public function it_calls_next_when_user_is_unauthenticated(): void
    {
        $request = new HttpRequest(url: '/dashboard', method: 'GET');
        $expectedResponse = new HttpResponse('/views/dashboard.php');
        $middleware = new WebGuestMiddleware();

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
    public function it_redirects_to_default_login_url_when_authenticated(): void
    {
        $_SESSION['user_id'] = 1;
        $request = new HttpRequest(url: '/protected-page', method: 'GET');
        $middleware = new WebGuestMiddleware();

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/', $response->headers['Location'] ?? null);
    }

    #[Test]
    public function it_redirects_to_custom_url_when_provided(): void
    {
        $_SESSION['user_id'] = 1;

        $request = new HttpRequest(url: '/admin/settings', method: 'GET');
        $middleware = new WebGuestMiddleware();

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, redirectAuthenticatedUserTo: '/custom-login');

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/custom-login', $response->headers['Location'] ?? null);
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $_SESSION['user_id'] = 1;

        $request = new HttpRequest(url: '/secret', method: 'GET');
        $customResponse = new HttpResponse('/views/custom-error.php', statusCode: 401);

        $customHandler = function (HttpRequest $req) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new WebGuestMiddleware(customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame($customResponse, $response);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $_SESSION['user_id'] = 1;

        $request = new HttpRequest(url: '/secret', method: 'GET');
        $customResponse = new HttpResponse('/views/custom-error.php', statusCode: 401);

        $customHandler = function (HttpRequest $req, $url) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return new HttpResponse($url, statusCode: 401);
        };

        $middleware = new WebGuestMiddleware(customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, '/views/custom-error.php');

        $this->assertEquals($customResponse, $response);
    }
}
