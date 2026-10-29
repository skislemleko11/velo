<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\GuestMiddleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Concrete\ViewResponse;
use Velo\Middlewares\GuestMiddleware\WebGuestConfig;
use Velo\Middlewares\GuestMiddleware\WebGuestMiddleware;
use Velo\Session\Session\Session;
use Velo\Session\Session\SessionInterface;

class WebGuestMiddlewareTest extends TestCase
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
        $expectedResponse = new ViewResponse('/views/dashboard.php');
        $middleware = new WebGuestMiddleware(session: $this->session);

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
    public function it_redirects_to_default_login_url_when_authenticated(): void
    {
        $_SESSION['user_id'] = 1;
        $request = new Request(url: '/protected-page', method: RequestMethod::GET);
        $middleware = new WebGuestMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next);

        self::assertSame(302, $response->statusCode);
        self::assertSame('/', $response->getHeader('Location'));
    }

    #[Test]
    public function it_redirects_to_custom_url_when_provided(): void
    {
        $config = new WebGuestConfig(redirectAuthenticatedUserTo: '/custom-login');
        $_SESSION[$config->sessionKeyName] = 1;

        $request = new Request(url: '/admin/settings', method: RequestMethod::GET);
        $middleware = new WebGuestMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, $config);

        self::assertSame(302, $response->statusCode);
        self::assertSame('/custom-login', $response->getHeader('Location'));
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

        $config = new WebGuestConfig(customResponseHandler: $customHandler);

        $_SESSION[$config->sessionKeyName] = 1;

        $middleware = new WebGuestMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, $config);

        self::assertSame($customResponse, $response);
    }

    #[Test]
    public function it_uses_custom_response_handler_with_custom_response_when_provided(): void
    {
        $request = new Request(url: '/secret', method: RequestMethod::GET);
        $customResponse = new ViewResponse('/views/custom-error.php', statusCode: 401);

        $customHandler = function (Request $req, $url) use ($request) {
            self::assertSame($request, $req);
            return new ViewResponse($url, statusCode: 401);
        };

        $config = new WebGuestConfig(
            redirectAuthenticatedUserTo: '/views/custom-error.php',
            customResponseHandler: $customHandler
        );

        $_SESSION[$config->sessionKeyName] = 1;

        $middleware = new WebGuestMiddleware(session: $this->session);

        $next = fn() => self::fail('Should not be called for unauthenticated user.');

        $response = $middleware->handle($request, $next, $config);

        self::assertEquals($customResponse, $response);
    }
}
