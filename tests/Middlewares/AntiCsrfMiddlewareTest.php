<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Container\Container;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Middlewares\AntiCsrfMiddleware;
use Velo\Middlewares\Exceptions\CannotUseThisMiddlewareWithGetMethodException;
use Velo\Router\PathResolver\PathResolver;

class AntiCsrfMiddlewareTest extends TestCase
{
    private AntiCsrfMiddleware $middleware;
    private Container $container;
    private PathResolver $pathResolver;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];

        $this->container = new Container();
        $this->pathResolver = new PathResolver(
            basePath: '/',
            publicPath: '/public/',
            viewsPath: '/views/',
            error403Path: null,
            error404Path: '/views/error404.php',
            error500Path: '/views/error500.php',
        );

        $this->container->set(PathResolver::class, fn() => $this->pathResolver);
        $this->middleware = $this->container->get(AntiCsrfMiddleware::class);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    #[Test]
    public function it_throws_exception_with_GET_method(): void
    {
        $this->expectException(CannotUseThisMiddlewareWithGetMethodException::class);
        $this->middleware->handle(new HttpRequest('/hehe', 'GET'), fn() => new HttpResponse());
    }

    #[Test]
    #[DataProvider('invalidSessionPostDataProvider')]
    public function it_handles_invalid_tokens(mixed $sessionToken, mixed $postToken): void
    {
        if ($sessionToken !== null) {
            $_SESSION['csrf_token'] = $sessionToken;
        }
        if ($postToken !== null) {
            $_POST['csrf_token'] = $postToken;
        }

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new HttpResponse('/next', 200);
        };

        $request = new HttpRequest('/hehe', 'POST');
        $response = $this->middleware->handle($request, $next);

        $this->assertFalse($nextCalled, 'Next middleware/controller should NOT be called on CSRF failure');
        $this->assertSame(403, $response->statusCode);
        $this->assertSame($this->pathResolver->getFilePath('error403'), $response->viewPath);

        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertIsString($_SESSION['csrf_token']);
        $this->assertSame(64, strlen($_SESSION['csrf_token']));
    }

    #[Test]
    public function it_passes_execution_to_next_when_tokens_match(): void
    {
        $validToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $validToken;
        $_POST['csrf_token'] = $validToken;

        $nextResponse = new HttpResponse('/success', 200);

        $response = $this->middleware->handle(
            new HttpRequest('/hehe', 'POST'),
            fn() => $nextResponse
        );

        $this->assertSame($nextResponse, $response);
    }

    #[Test]
    #[DataProvider('invalidSessionPostDataProvider')]
    public function it_uses_custom_response_handler_when_provided(mixed $sessionToken, mixed $postToken): void
    {
        if ($sessionToken !== null) {
            $_SESSION['csrf_token'] = $sessionToken;
        }
        if ($postToken !== null) {
            $_POST['csrf_token'] = $postToken;
        }

        $closureResponse = new HttpResponse('/custom-error', 403, ['error' => 'Custom']);

        $middleware = new AntiCsrfMiddleware(
            $this->pathResolver,
            fn(HttpRequest $req) => $closureResponse
        );

        $response = $middleware->handle(
            new HttpRequest('/hehe', 'POST'),
            fn() => new HttpResponse()
        );

        $this->assertSame($closureResponse, $response);
    }

    public static function invalidSessionPostDataProvider(): array
    {
        return [
            'missing_session_token' => [null, 'valid_token'],
            'missing_post_token'    => ['valid_token', null],
            'mismatched_tokens'     => ['token_a', 'token_b'],
            'int_session_token'     => ['12345', 2345],
            'empty_string_post'     => ['valid_token', ''],
            'boolean_post'          => ['valid_token', true],
        ];
    }
}