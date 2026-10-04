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
use Velo\Middlewares\AntiCsrf\AntiCsrfConfig;
use Velo\Middlewares\AntiCsrf\AntiCsrfMiddleware;
use Velo\Session\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
final class AntiCsrfMiddlewareTest extends TestCase
{
    private AntiCsrfMiddleware $middleware;
    private PathResolver $pathResolver;
    private SessionInterface $session;
    private AntiCsrfConfig $config;

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
        $this->config = new AntiCsrfConfig();
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    #[Test]
    #[DataProvider('invalidTokenProvider')]
    public function it_handles_invalid_tokens_from_json_and_post_if_json_one_does_not_exist(
        mixed $sessionToken,
        mixed $requestTokenPost,
        mixed $requestTokenJson
    ): void
    {
        if ($requestTokenJson !== null) {
            $jsonContent = json_encode(['csrf_token' => $requestTokenJson]);
        }

        if ($requestTokenPost !== null) {
            $_POST['csrf_token'] = $requestTokenPost;
        }

        $this->willReturnCsrfToken($sessionToken);

        $this->expects64LengthTokenSet();

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new ViewResponse('/next');
        };

        $request = $this->getRequestWithJsonData(content: $jsonContent ?? '');

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
            'missing_session_token' => [null, 'valid_token', null],
            'missing_request_token' => ['valid_token', null, null],
            'mismatched_tokens_post' => ['token_a', 'token_b', null],
            'mismatched_tokens_json' => ['token_a', null, 'token_b'],
            'empty_session_token' => ['', 'valid_token', 'valid_token'],
            'empty_post_token' => ['valid_token', '', null],
            'empty_json_token' => ['valid_token', null, ''],
            'prioritizes_json_token_which_is_invalid_here' => ['token_a', 'token_a', 'token_b']
        ];
    }

    private function willReturnCsrfToken(mixed $token): void
    {
        $this->session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME)
            ->willReturn($token);
    }

    private function expects64LengthTokenSet(): void
    {
        $this->session->expects(self::once())
            ->method('set')
            ->with(
                AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME,
                self::callback(
                    static fn(mixed $token): bool => is_string($token) && strlen($token) === 64
                )
            );
    }

    private function getRequestWithJsonData(string $url = 'hehe', RequestMethod $method = RequestMethod::POST, string $content = ''): Request
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode($content));
        rewind($stream);

        $streamUrl = 'data://text/plain;base64,' . base64_encode($content);

        return new Request($url, $method, $streamUrl);
    }

    private function getFilePath(ViewResponse $response): string
    {
        $reflection = new ReflectionClass($response);

        return $reflection->getProperty('relativeToViewsDirFilePath')->getValue($response);
    }

    #[Test]
    #[DataProvider('matchingTokensProvider')]
    public function it_passes_execution_to_next_when_tokens_match(
        string  $sessionToken,
        ?string $requestTokenPost,
        ?string $requestTokenJson
    ): void
    {
        $this->willReturnCsrfToken($sessionToken);

        $this->session
            ->expects(self::never())
            ->method('set');

        if ($requestTokenPost !== null) {
            $_POST[AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME] = $requestTokenPost;
        }

        if ($requestTokenJson !== null) {
            $jsonContent = json_encode(['csrf_token' => $requestTokenJson]);
        }

        $request = $this->getRequestWithJsonData(content: $jsonContent ?? '');
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

    /**
     * @return array<string, list<string|null>>
     */
    public static function matchingTokensProvider(): array
    {
        return [
            'json_token_matches_post_one_does_not_exist' => ['hehe', null, 'hehe'],
            'json_token_matches_post_one_is_wrong' => ['hehe', 'nope', 'hehe'],
            'post_token_matches_json_one_does_not_exist' => ['hehe', 'hehe', null]
        ];
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
        mixed $postToken,
        mixed $jsonToken
    ): void
    {
        if ($postToken !== null) {
            $_POST[AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME] = $postToken;
        }

        if ($jsonToken !== null) {
            $jsonContent = json_encode(['csrf_token' => $jsonToken]);
        }

        $this->willReturnCsrfToken($sessionToken);

        $this->expects64LengthTokenSet();

        $nextCalled = false;

        $request = $this->getRequestWithJsonData(content: $jsonContent ?? '');

        $this->middleware->handle(
            $request,
            function () use (&$nextCalled) {
                $nextCalled = true;

                return new ViewResponse('/success');
            },
            $this->config
        );

        self::assertFalse($nextCalled);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function invalidTokenTypeProvider(): array
    {
        return [
            'array_session_token' => [
                ['csrf_token'],
                'valid-token',
                null,
            ],
            'array_post_token' => [
                'valid-token',
                ['csrf_token'],
                null,
            ],
            'array_json_token' => [
                'valid-token',
                'valid-token',
                ['csrf_token'],
            ],
            'integer_session_token' => [
                12345,
                '12345',
                null,
            ],
            'integer_post_token' => [
                '12345',
                12345,
                null,
            ],
            'integer_json_token' => [
                '12345',
                'valid-token',
                12345,
            ],
            'boolean_session_token' => [
                true,
                '1',
                null,
            ],
            'boolean_post_token' => [
                'valid-token',
                true,
                null,
            ],
            'boolean_json_token' => [
                'valid-token',
                'valid-token',
                true,
            ],
            'null_session_token' => [
                null,
                'valid-token',
                null,
            ],
            'null_post_token' => [
                'valid-token',
                null,
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
            $this->session
        );

        $response = $middleware->handle(
            $request,
            static fn() => new ViewResponse('/success'),
            new AntiCsrfConfig(
                customResponseHandler: function (Request $receivedRequest) use (
                    $request,
                    $customResponse,
                    &$handlerCalled
                ): Response {
                    $handlerCalled = true;

                    self::assertSame($request, $receivedRequest);

                    return $customResponse;
                }
            )
        );

        self::assertTrue($handlerCalled);
        self::assertSame($customResponse, $response);
    }

    #[Test]
    public function it_accepts_custom_token_name_when_tokens_match(): void
    {
        $this->willReturnCsrfToken('custom-token');

        $this->session
            ->expects(self::never())
            ->method('set');

        $jsonContent = json_encode(['my_token' => 'custom-token']);

        $request = $this->getRequestWithJsonData(content: $jsonContent);

        $nextResponse = new ViewResponse('/ok');

        $response = $this->middleware->handle(
            $request,
            function (Request $receivedRequest) use ($request, $nextResponse) {
                self::assertSame($request, $receivedRequest);

                return $nextResponse;
            },
            new AntiCsrfConfig(tokenName: 'my_token')
        );

        self::assertSame($nextResponse, $response);
    }

    #[Test]
    public function it_rejects_invalid_token_for_custom_token_name(): void
    {
        $this->willReturnCsrfToken('session_token');

        $this->expects64LengthTokenSet();

        $jsonContent = json_encode(['my_token' => ['not', 'string']]);

        $request = $this->getRequestWithJsonData(content: $jsonContent);

        $response = $this->middleware->handle(
            $request,
            static fn() => new ViewResponse('/success'),
            new AntiCsrfConfig(tokenName: 'my_token')
        );

        self::assertSame(403, $response->statusCode);
    }
}