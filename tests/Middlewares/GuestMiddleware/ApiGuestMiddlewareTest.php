<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\GuestMiddleware;

use PHPUnit\Framework\Attributes\Test;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\TextResponse;
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
        $request = new Request(url: '/dashboard', method: RequestMethod::GET);
        $expectedResponse = new TextResponse('hehe');
        $middleware = new ApiGuestMiddleware(session: $this->session);

        $nextCalled = false;
        $next = function (Request $req) use (&$nextCalled, $expectedResponse) {
            $nextCalled = true;
            return $expectedResponse;
        };

        $response = $middleware->handle($request, $next);

        self::assertTrue($nextCalled);
        self::assertSame($expectedResponse, $response);
    }

    #[Test]
    public function it_returns_authenticated_response_when_user_is_authenticated(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new Request(url: '/protected-page', method: RequestMethod::GET);
        $middleware = new ApiGuestMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        self::assertSame(403, $response->statusCode);
        self::assertNull($response->getHeader('Location'));
    }

    #[Test]
    public function it_returns_custom_authenticated_response_when_provided(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new Request(url: '/admin/settings', method: RequestMethod::GET);
        $middleware = new ApiGuestMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, responseForAuthenticatedUser: ['error' => 'hehe']);

        self::assertSame(403, $response->statusCode);
        self::assertNull($response->getHeader('Location'));
        self::assertSame(['error' => 'hehe'], $response->body ?? null);
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new Request(url: '/secret', method: RequestMethod::GET);
        $customResponse = new TextResponse('', statusCode: 401);

        $customHandler = function (Request $req) use ($request, $customResponse) {
            self::assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new ApiGuestMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        self::assertSame($customResponse, $response);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new Request(url: '/secret', method: RequestMethod::GET);
        $customResponse = new JsonResponse(body: ['hehe' => 'hihi'], statusCode: 401);

        $customHandler = function (Request $req, $data) use ($request) {
            self::assertSame($request, $req);
            return new JsonResponse(body: $data, statusCode: 401);
        };

        $middleware = new ApiGuestMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, ['hehe' => 'hihi']);

        self::assertEquals($customResponse, $response);
    }
}
