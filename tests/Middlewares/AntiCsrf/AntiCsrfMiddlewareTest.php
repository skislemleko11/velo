<?php
declare(strict_types=1);

namespace Middlewares\AntiCsrf;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
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
use Velo\Middlewares\AntiCsrf\AntiCsrfTokenManagerInterface;

#[AllowMockObjectsWithoutExpectations]
final class AntiCsrfMiddlewareTest extends TestCase
{
    private AntiCsrfMiddleware $middleware;
    private PathResolver&MockObject $pathResolver;
    private AntiCsrfTokenManagerInterface&MockObject $tokenManager;

    protected function setUp(): void
    {
        $_POST = [];
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $this->pathResolver = self::createMock(PathResolver::class);
        $this->tokenManager = self::createMock(AntiCsrfTokenManagerInterface::class);
        $this->middleware = new AntiCsrfMiddleware($this->pathResolver, $this->tokenManager);
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

        $expectedRequestToken = $requestTokenJson ?? ($_POST['csrf_token'] ?? null);

        $this->expectsTokenValidation($expectedRequestToken, false);
        $this->expectsTokenRegeneration();
        $this->pathResolverWillReturn403ErrorPath();

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return new ViewResponse('/next');
        };

        $request = $this->getRequestWithJsonData($jsonContent ?? '');

        $response = $this->middleware->handle($request, $next);

        self::assertFalse($nextCalled, 'Next middleware/controller should NOT be called on CSRF failure');
        self::assertSame(403, $response->statusCode);
        self::assertInstanceOf(ViewResponse::class, $response);
        self::assertSame(
            'error403.php',
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

    private function expectsTokenValidation(mixed $token, bool $isValid): void
    {
        $this->tokenManager->expects(self::once())
            ->method('isValid')
            ->with($token)
            ->willReturn($isValid);
    }

    private function expectsTokenRegeneration(string $newToken = 'generated-token'): void
    {
        $this->tokenManager->expects(self::once())
            ->method('regenerateToken')
            ->willReturn($newToken);
    }

    private function pathResolverWillReturn403ErrorPath(): void
    {
        $this->pathResolver->expects(self::once())
            ->method('resolveErrorFilePath')
            ->with(403)
            ->willReturn('error403.php');
    }

    private function getRequestWithJsonData(string $content = ''): Request
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode($content));
        rewind($stream);

        $streamUrl = 'data://text/plain;base64,' . base64_encode($content);

        return new Request('', RequestMethod::POST, $streamUrl);
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
        $expectedRequestToken = $requestTokenJson ?? $requestTokenPost;

        $this->tokenManager->expects(self::once())
            ->method('isValid')
            ->with($expectedRequestToken)
            ->willReturn(true);

        $this->tokenManager
            ->expects(self::never())
            ->method('regenerateToken');

        if ($requestTokenPost !== null) {
            $_POST[AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME] = $requestTokenPost;
        }

        if ($requestTokenJson !== null) {
            $jsonContent = json_encode(['csrf_token' => $requestTokenJson]);
        }

        $request = $this->getRequestWithJsonData($jsonContent ?? '');
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
        $this->expectsTokenValidation(null, false);
        $this->expectsTokenRegeneration();

        $middleware = new AntiCsrfMiddleware(
            $this->pathResolver,
            $this->tokenManager
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

        $expectedRequestToken = $jsonToken ?? $postToken;

        $this->expectsTokenValidation($expectedRequestToken, false);
        $this->expectsTokenRegeneration();

        $nextCalled = false;

        $request = $this->getRequestWithJsonData($jsonContent ?? '');

        $this->middleware->handle(
            $request,
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

        $this->expectsTokenValidation(null, false);
        $this->expectsTokenRegeneration();

        $handlerCalled = false;

        $middleware = new AntiCsrfMiddleware(
            $this->pathResolver,
            $this->tokenManager
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
        $jsonContent = json_encode(['my_token' => 'custom-token']);

        $this->tokenManager->expects(self::once())
            ->method('isValid')
            ->with('custom-token')
            ->willReturn(true);

        $this->tokenManager
            ->expects(self::never())
            ->method('regenerateToken');

        $request = $this->getRequestWithJsonData($jsonContent);

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
        $this->expectsTokenValidation(['not', 'string'], false);
        $this->expectsTokenRegeneration();

        $jsonContent = json_encode(['my_token' => ['not', 'string']]);

        $request = $this->getRequestWithJsonData($jsonContent);

        $response = $this->middleware->handle(
            $request,
            static fn() => new ViewResponse('/success'),
            new AntiCsrfConfig(tokenName: 'my_token')
        );

        self::assertSame(403, $response->statusCode);
    }

    #[Test]
    public function it_gets_json_error_response_when_accept_is_json(): void
    {
        $this->expectsTokenValidation(null, false);
        $this->expectsTokenRegeneration();

        $this->pathResolver->expects(self::never())
            ->method('resolveErrorFilePath');

        $request = $this->getRequestWithJsonData();

        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $response = $this->middleware->handle(
            $request,
            function (Request $r) {
                self::fail('Expected JSON error response');
            }
        );

        self::assertSame(403, $response->statusCode);
        self::assertInstanceOf(JsonResponse::class, $response);
    }
}