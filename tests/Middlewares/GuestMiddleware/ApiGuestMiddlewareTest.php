<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\GuestMiddleware;

use PHPUnit\Framework\Attributes\Test;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Middlewares\GuestMiddleware\ApiGuestMiddleware;
use PHPUnit\Framework\TestCase;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\Session\Session\Session;
use Velo\Http\RequestMethod;

class ApiGuestMiddlewareTest extends TestCase
{
    private SessionInterface $session;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->session = new Session();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function it_calls_next_when_user_is_unauthenticated(): void
    {
        $request = new HttpRequest(url: '/dashboard', method: RequestMethod::GET);
        $expectedResponse = HttpResponse::plainText('hehe');
        $middleware = new ApiGuestMiddleware(session: $this->session);

        $nextCalled = false;
        $next = function (HttpRequest $req) use (&$nextCalled, $expectedResponse) {
            $nextCalled = true;
            return $expectedResponse;
        };

        $response = $middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertSame($expectedResponse, $response);
    }

    #[Test]
    public function it_returns_authenticated_response_when_user_is_authenticated(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new HttpRequest(url: '/protected-page', method: RequestMethod::GET);
        $middleware = new ApiGuestMiddleware(session: $this->session);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame(403, $response->statusCode);
        $this->assertNull($response->headers['Location'] ?? null);
    }

    #[Test]
    public function it_returns_custom_authenticated_response_when_provided(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new HttpRequest(url: '/admin/settings', method: RequestMethod::GET);
        $middleware = new ApiGuestMiddleware(session: $this->session);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, responseForAuthenticatedUser: ['error' => 'hehe']);

        $this->assertSame(403, $response->statusCode);
        $this->assertNull($response->headers['Location'] ?? null);
        $this->assertSame(['error' => 'hehe'], $response->body);
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new HttpRequest(url: '/secret', method: RequestMethod::GET);
        $customResponse = HttpResponse::plainText('', statusCode: 401);

        $customHandler = function (HttpRequest $req) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new ApiGuestMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame($customResponse, $response);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new HttpRequest(url: '/secret', method: RequestMethod::GET);
        $customResponse = HttpResponse::json(body: ['hehe' => 'hihi'], statusCode: 401);

        $customHandler = function (HttpRequest $req, $data) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return HttpResponse::json(body: $data, statusCode: 401);
        };

        $middleware = new ApiGuestMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, ['hehe' => 'hihi']);

        $this->assertEquals($customResponse, $response);
    }
}
