<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\Cors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Cors\CorsConfig\CorsConfig;
use Velo\Middlewares\Cors\CorsMiddleware;

final class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CorsMiddleware();
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
    }

    #[Test]
    public function it_adds_cors_headers_to_allowed_request(): void
    {
        $request = $this->createRequest(
            RequestMethod::GET,
            [
                'Origin' => 'https://example.com',
            ]
        );

        $response = new NoContentResponse(200);

        $nextCalled = false;

        $result = $this->middleware->handle(
            $request,
            function () use (&$nextCalled, $response): Response {
                $nextCalled = true;

                return $response;
            },
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
            ),
        );

        self::assertTrue($nextCalled);
        self::assertSame($response, $result);

        self::assertSame(
            'https://example.com',
            $result->getHeader('Access-Control-Allow-Origin')
        );
    }

    #[Test]
    public function it_does_not_add_cors_headers_when_origin_is_not_allowed(): void
    {
        $request = $this->createRequest(
            RequestMethod::GET,
            [
                'Origin' => 'https://evil.example',
            ]
        );

        $response = new NoContentResponse(200);

        $result = $this->middleware->handle(
            $request,
            static fn() => $response,
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
            ),
        );

        self::assertSame($response, $result);
        self::assertNull($result->getHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function it_does_not_add_cors_headers_when_method_is_not_allowed(): void
    {
        $request = $this->createRequest(
            RequestMethod::POST,
            [
                'Origin' => 'https://example.com',
            ]
        );

        $response = new NoContentResponse(200);

        $result = $this->middleware->handle(
            $request,
            static fn() => $response,
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::GET],
            ),
        );

        self::assertNull($result->getHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function it_adds_wildcard_cors_header_when_all_origins_are_allowed(): void
    {
        $request = $this->createRequest(
            RequestMethod::GET,
            [
                'Origin' => 'https://example.com',
            ]
        );

        $response = new NoContentResponse(200);

        $result = $this->middleware->handle(
            $request,
            static fn() => $response,
            new CorsConfig(),
        );

        self::assertSame(
            '*',
            $result->getHeader('Access-Control-Allow-Origin')
        );
    }

    #[Test]
    public function it_does_not_add_cors_headers_when_origin_header_is_missing(): void
    {
        $request = $this->createRequest(RequestMethod::GET);

        $response = new NoContentResponse(200);

        $nextCalled = false;

        $result = $this->middleware->handle(
            $request,
            function () use (&$nextCalled, $response): Response {
                $nextCalled = true;

                return $response;
            },
        );

        self::assertTrue($nextCalled);
        self::assertSame($response, $result);

        self::assertNull($result->getHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function it_appends_origin_to_existing_vary_header_on_allowed_request(): void
    {
        $request = $this->createRequest(
            RequestMethod::GET,
            [
                'Origin' => 'https://example.com',
            ]
        );

        $response = new NoContentResponse(200, ['Vary' => 'accept-language']);

        $result = $this->middleware->handle(
            $request,
            static fn() => $response,
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
            ),
        );

        self::assertSame('accept-language, origin', $result->getHeader('Vary'));
        self::assertSame('https://example.com', $result->getHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function it_allows_credentials_when_configured(): void
    {
        $request = $this->createRequest(
            RequestMethod::GET,
            [
                'Origin' => 'https://example.com',
            ]
        );

        $response = new NoContentResponse(200);

        $result = $this->middleware->handle(
            $request,
            static fn() => $response,
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowCredentials: true,
            ),
        );

        self::assertSame(
            'https://example.com',
            $result->getHeader('Access-Control-Allow-Origin')
        );

        self::assertSame(
            'true',
            $result->getHeader('Access-Control-Allow-Credentials')
        );

        self::assertSame(
            'origin',
            $result->getHeader('Vary')
        );
    }

    #[Test]
    public function it_returns_no_content_response_for_successful_preflight(): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'Content-Type, Authorization',
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a successful preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [
                    RequestMethod::GET,
                    RequestMethod::POST,
                ],
                allowedHeaders: [
                    'content-type',
                    'authorization',
                ],
            ),
        );

        self::assertInstanceOf(NoContentResponse::class, $result);
        self::assertSame(204, $result->statusCode);

        self::assertSame(
            'https://example.com',
            $result->getHeader('Access-Control-Allow-Origin')
        );

        self::assertSame(
            'GET, POST',
            $result->getHeader('Access-Control-Allow-Methods')
        );

        self::assertSame(
            'content-type, authorization',
            $result->getHeader('Access-Control-Allow-Headers')
        );
    }

    #[Test]
    public function it_allows_preflight_without_requested_headers(): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
                'Access-Control-Request-Method' => 'POST',
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a successful preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::POST],
            ),
        );

        self::assertInstanceOf(NoContentResponse::class, $result);
        self::assertSame(204, $result->statusCode);
    }

    #[Test]
    public function it_returns_forbidden_response_for_preflight_with_disallowed_method(): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
                'Access-Control-Request-Method' => 'DELETE',
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a rejected preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::GET],
            ),
        );

        self::assertInstanceOf(NoContentResponse::class, $result);
        self::assertSame(403, $result->statusCode);
    }

    #[Test]
    #[DataProvider('headersProvider')]
    public function it_returns_forbidden_response_for_preflight_with_disallowed_header(string $requestedHeader): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => $requestedHeader
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a rejected preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::POST],
                allowedHeaders: ['content-type'],
            ),
        );

        self::assertInstanceOf(NoContentResponse::class, $result);
        self::assertSame(403, $result->statusCode);
    }

    #[Test]
    #[DataProvider('headersProvider')]
    public function it_returns_response_for_preflight_with_allowed_header_case_insensitive(string $requestedHeader): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => $requestedHeader
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a successful preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::POST],
                allowedHeaders: ['a', 'hehe']
            ),
        );

        self::assertInstanceOf(NoContentResponse::class, $result);
        self::assertSame(204, $result->statusCode);
    }

    public static function headersProvider(): array
    {
        return [
            ['A'],
            ['a'],
            ['Hehe'],
            ['HehE'],
            ['HEHE'],
            ['hehe']
        ];
    }

    #[Test]
    public function it_allows_preflight_with_wildcard_headers(): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'Authorization, X-Custom-Header',
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a successful preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::POST],
                allowedHeaders: ['*'],
            ),
        );

        self::assertSame(204, $result->statusCode);
    }

    #[Test]
    public function it_returns_forbidden_response_for_preflight_with_disallowed_origin(): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://evil.example',
                'Access-Control-Request-Method' => 'POST',
            ]
        );

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a rejected preflight.'
            ),
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                allowedMethods: [RequestMethod::POST],
            ),
        );

        self::assertSame(403, $result->statusCode);
    }

    #[Test]
    public function it_passes_ordinary_options_request_to_next_middleware(): void
    {
        $request = $this->createRequest(
            RequestMethod::OPTIONS,
            [
                'Origin' => 'https://example.com',
            ]
        );

        $response = new NoContentResponse(200);

        $nextCalled = false;

        $result = $this->middleware->handle(
            $request,
            function () use (&$nextCalled, $response): Response {
                $nextCalled = true;

                return $response;
            },
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
            ),
        );

        self::assertTrue($nextCalled);
        self::assertSame($response, $result);
    }

    #[Test]
    public function it_rejects_preflight_with_invalid_requested_method(): void
    {
        $request = $this->createRequest(RequestMethod::OPTIONS, [
            'origin' => 'hehe',
            'access-control-request-method' => 'no'
        ]);

        $result = $this->middleware->handle(
            $request,
            static fn(): never => self::fail(
                'The next middleware must not be called for a rejected preflight.'
            ),
            new CorsConfig(),
        );

        self::assertSame(403, $result->statusCode);
    }

    private function createRequest(
        RequestMethod $method,
        array         $headers = [],
    ): Request
    {
        $request = new Request('', $method);

        foreach ($headers as $name => $value) {
            $_SERVER['HTTP_' . $name] = $value;
        }

        return $request;
    }
}