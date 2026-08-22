<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\AuthMiddleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\TextResponse;
use Velo\Middlewares\AuthMiddleware\ApiAuthMiddleware;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\Session\Session\Session;
use Velo\Http\RequestMethod;

final class ApiAuthMiddlewareTest extends TestCase
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
    public function it_calls_next_when_user_is_authenticated(): void
    {
        $_SESSION['user_id'] = 123;

        $request = new Request(url: '/dashboard', method: RequestMethod::GET);
        $expectedResponse = new TextResponse('hehe');
        $middleware = new ApiAuthMiddleware(session: $this->session);

        $nextCalled = false;
        $next = function (Request $req) use (&$nextCalled, $expectedResponse) {
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
        $request = new Request(url: '/protected-page', method: RequestMethod::GET);
        $middleware = new ApiAuthMiddleware(session: $this->session);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame(401, $response->statusCode);
        $this->assertNull($response->headers['Location'] ?? null);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }

    #[Test]
    public function it_returns_custom_unauthenticated_response_when_provided(): void
    {
        $request = new Request(url: '/admin/settings', method: RequestMethod::GET);
        $middleware = new ApiAuthMiddleware(session: $this->session);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, responseForUnauthenticatedUser: ['error' => 'hehe']);

        $this->assertSame(401, $response->statusCode);
        $this->assertNull($response->headers['Location'] ?? null);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
        $this->assertSame(['error' => 'hehe'], $response->body);
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $request = new Request(url: '/secret', method: RequestMethod::GET);
        $customResponse = new TextResponse('', statusCode: 401);

        $customHandler = function (Request $req) use ($request, $customResponse) {
            $this->assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new ApiAuthMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        $this->assertSame($customResponse, $response);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $request = new Request(url: '/secret', method: RequestMethod::GET);

        $expectedResponse = new JsonResponse(body: ['hehe' => 'hihi']);

        $customHandler = function (Request $req, $responseForUnauthenticatedUser) use ($request) {
            $this->assertSame($request, $req);
            return new JsonResponse(body: $responseForUnauthenticatedUser);
        };

        $middleware = new ApiAuthMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => $this->fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, ['hehe' => 'hihi']);

        $this->assertEquals($expectedResponse, $response);
        $this->assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }
}