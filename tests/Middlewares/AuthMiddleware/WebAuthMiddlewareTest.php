<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\AuthMiddleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Middlewares\AuthMiddleware\WebAuthMiddleware;

class WebAuthMiddlewareTest extends TestCase
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
        $expectedResponse = new HttpResponse('/views/dashboard.php');
        $middleware = new WebAuthMiddleware();

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
    public function it_redirects_to_default_login_url_and_saves_intended_url_when_unauthenticated(): void
    {
        $request = new HttpRequest(url: '/protected-page', method: 'GET');
        $middleware = new WebAuthMiddleware();

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/login', $response->headers['Location'] ?? null);
        $this->assertSame('/protected-page', $_SESSION['redirect_after_login'] ?? null);
    }

    #[Test]
    public function it_redirects_to_custom_url_when_provided(): void
    {
        $request = new HttpRequest(url: '/admin/settings', method: 'GET');
        $middleware = new WebAuthMiddleware();

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, redirectUrl: '/custom-login');

        $this->assertSame(302, $response->statusCode);
        $this->assertSame('/custom-login', $response->headers['Location'] ?? null);
        $this->assertSame('/admin/settings', $_SESSION['redirect_after_login'] ?? null);
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $request = new HttpRequest(url: '/secret', method: 'GET');
        $customResponse = new HttpResponse('/views/custom-error.php', statusCode: 401);

        $customHandler = function (HttpRequest $req) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new WebAuthMiddleware(customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame($customResponse, $response);
        $this->assertSame('/secret', $_SESSION['redirect_after_login'] ?? null);
    }
}