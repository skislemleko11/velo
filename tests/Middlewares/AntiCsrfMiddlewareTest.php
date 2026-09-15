<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\ViewResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\AntiCsrfMiddleware;
use Velo\Session\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
final class AntiCsrfMiddlewareTest extends TestCase
{
    private AntiCsrfMiddleware $middleware;
    private PathResolver $pathResolver;
    private SessionInterface $session;

    protected function setUp(): void
    {
        $_POST = [];

        $this->pathResolver = new PathResolver()
            ->setDirPath(PathResolver::ROOT_DIR_KEY, '/')
            ->setDirPath(PathResolver::PUBLIC_DIR_KEY, '/public/')
            ->setDirPath(PathResolver::VIEWS_DIR_KEY, '/views/')
            ->setErrorGeneralFilePath('error.php')
            ->setErrorFilePath(403, 'error403.php')
            ->setErrorFilePath(404, 'error404.php')
            ->setErrorFilePath(500, 'error500.php');

        $this->session = $this->createMock(SessionInterface::class);
        $this->middleware = new AntiCsrfMiddleware($this->pathResolver, $this->session);
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    #[Test]
    #[DataProvider('invalidTokenProvider')]
    public function it_handles_invalid_tokens(mixed $sessionToken, mixed $postToken): void
    {
        if ($postToken !== null) {
            $_POST['csrf_token'] = $postToken;
        }

        $this->willReturnCsrfToken($sessionToken);

        $this->expects64LengthTokenSet();

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new ViewResponse('/next');
        };

        $request = new Request('/hehe', RequestMethod::POST);

        $response = $this->middleware->handle($request, $next);

        self::assertFalse($nextCalled, 'Next middleware/controller should NOT be called on CSRF failure');
        self::assertSame(403, $response->statusCode);
        self::assertInstanceOf(ViewResponse::class, $response);
        self::assertSame(
            $this->pathResolver->getErrorFilePath(403),
            $this->getFilePath($response)
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function invalidTokenProvider(): array
    {
        return [
            'missing_session_token' => [null, 'valid_token'],
            'missing_post_token' => ['valid_token', null],
            'mismatched_tokens' => ['token_a', 'token_b'],
            'empty_session_token' => ['', 'valid_token'],
            'empty_post_token' => ['valid_token', ''],
        ];
    }

    private function willReturnCsrfToken(mixed $token): void
    {
        $this->session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_TOKEN_NAME)
            ->willReturn($token);
    }

    private function expects64LengthTokenSet(): void
    {
        $this->session->expects(self::once())
            ->method('set')
            ->with(
                AntiCsrfMiddleware::CSRF_TOKEN_NAME,
                self::callback(
                    static fn(mixed $token): bool => is_string($token) && strlen($token) === 64
                )
            );
    }

    private function getFilePath(ViewResponse $response): string
    {
        $reflection = new ReflectionClass($response);

        return $reflection->getProperty('relativeToViewsDirFilePath')->getValue($response);
    }

    #[Test]
    public function it_passes_execution_to_next_when_tokens_match(): void
    {
        $validToken = bin2hex(random_bytes(32));

        $this->willReturnCsrfToken($validToken);

        $this->session
            ->expects(self::never())
            ->method('set');

        $_POST[AntiCsrfMiddleware::CSRF_TOKEN_NAME] = $validToken;

        $request = new Request('/hehe', RequestMethod::POST);
        $nextResponse = new ViewResponse('/success');

        $response = $this->middleware->handle(
            $request,
            function (Request $receivedRequest) use ($request, $nextResponse) {
                self::assertSame($request, $receivedRequest);

                return $nextResponse;
            }
        );

        self::assertSame($nextResponse, $response);
    }

    #[Test]
    public function it_returns_json_response_when_403_view_is_not_registered(): void
    {
        $this->willReturnCsrfToken('session_token');
        $this->expects64LengthTokenSet();

        $middleware = new AntiCsrfMiddleware(
            new PathResolver(),
            $this->session
        );

        $response = $middleware->handle(
            new Request('/hehe', RequestMethod::POST),
            static fn() => new ViewResponse('/success')
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->statusCode);
    }

    #[Test]
    #[DataProvider('invalidTokenTypeProvider')]
    public function it_rejects_non_string_tokens(
        mixed $sessionToken,
        mixed $postToken
    ): void
    {
        $_POST[AntiCsrfMiddleware::CSRF_TOKEN_NAME] = $postToken;

        $this->willReturnCsrfToken($sessionToken);

        $this->expects64LengthTokenSet();

        $nextCalled = false;

        $this->middleware->handle(
            new Request('/hehe', RequestMethod::POST),
            function () use (&$nextCalled) {
                $nextCalled = true;

                return new ViewResponse('/success');
            }
        );

        self::assertFalse($nextCalled);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function invalidTokenTypeProvider(): array
    {
        return [
            'array session token' => [
                ['csrf_token'],
                'valid-token',
            ],
            'array post token' => [
                'valid-token',
                ['csrf_token'],
            ],
            'integer session token' => [
                12345,
                '12345',
            ],
            'integer post token' => [
                '12345',
                12345,
            ],
            'boolean session token' => [
                true,
                '1',
            ],
            'boolean post token' => [
                'valid-token',
                true,
            ],
            'null session token' => [
                null,
                'valid-token',
            ],
            'null post token' => [
                'valid-token',
                null,
            ],
        ];
    }

    #[Test]
    public function it_uses_custom_response_handler_and_passes_request_to_it(): void
    {
        $request = new Request('/protected', RequestMethod::POST);
        $customResponse = new ViewResponse(
            '/custom-error',
            data: ['error' => 'Custom'],
            statusCode: 418
        );

        $this->willReturnCsrfToken(null);

        $this->expects64LengthTokenSet();

        $handlerCalled = false;

        $middleware = new AntiCsrfMiddleware(
            $this->pathResolver,
            $this->session,
            function (Request $receivedRequest) use (
                $request,
                $customResponse,
                &$handlerCalled
            ): Response {
                $handlerCalled = true;

                self::assertSame($request, $receivedRequest);

                return $customResponse;
            }
        );

        $response = $middleware->handle(
            $request,
            static fn() => new ViewResponse('/success')
        );

        self::assertTrue($handlerCalled);
        self::assertSame($customResponse, $response);
    }
}