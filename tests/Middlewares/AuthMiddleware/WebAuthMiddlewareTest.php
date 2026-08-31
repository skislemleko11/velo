<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\AuthMiddleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\ViewResponse;
use Velo\Middlewares\AuthMiddleware\WebAuthMiddleware;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\Session\Session\Session;
use Velo\Http\RequestMethod;

final class WebAuthMiddlewareTest extends TestCase
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
        $expectedResponse = new ViewResponse('/views/dashboard.php');
        $middleware = new WebAuthMiddleware(session: $this->session);

        $nextCalled = false;
        $next = function (Request $req) use (&$nextCalled, $expectedResponse) {
            $nextCalled = true;
            return $expectedResponse;
        };

        $response = $middleware->handle($request, $next);

        self::assertTrue($nextCalled);
        self::assertSame($expectedResponse, $response);
        self::assertArrayNotHasKey('redirect_after_login', $_SESSION);
    }

    #[Test]
    public function it_redirects_to_default_login_url_and_saves_intended_url_when_unauthenticated(): void
    {
        $request = new Request(url: '/protected-page', method: RequestMethod::GET);
        $middleware = new WebAuthMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        self::assertSame(302, $response->statusCode);
        self::assertSame('/login?redirect=%2Fprotected-page', $response->getHeader('Location'));
    }

    #[Test]
    public function it_redirects_to_custom_url_when_provided(): void
    {
        $request = new Request(url: '/admin/settings', method: RequestMethod::GET);
        $middleware = new WebAuthMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, redirectUnauthenticatedUserTo: '/custom-login');

        self::assertSame(302, $response->statusCode);
        self::assertSame('/custom-login?redirect=%2Fadmin%2Fsettings', $response->getHeader('Location'));
    }

    #[Test]
    public function it_uses_custom_response_handler_when_provided(): void
    {
        $request = new Request(url: '/secret', method: RequestMethod::GET);
        $customResponse = new ViewResponse('/views/custom-error.php', statusCode: 401);

        $customHandler = function (Request $req) use ($request, $customResponse) {
            self::assertSame($request, $req);
            return $customResponse;
        };

        $middleware = new WebAuthMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        self::assertSame($customResponse, $response);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $request = new Request(url: '/secret', method: RequestMethod::GET);
        $customResponse = new ViewResponse('/views/custom-error.php?redirect=%2Fsecret', statusCode: 401);

        $customHandler = function (Request $req, $responseForUnauthenticatedUser) use ($request) {
            self::assertSame($request, $req);
            return new ViewResponse($responseForUnauthenticatedUser, statusCode: 401);
        };

        $middleware = new WebAuthMiddleware(session: $this->session, customResponseHandler: $customHandler);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, '/views/custom-error.php');

        self::assertEquals($customResponse, $response);
    }
}