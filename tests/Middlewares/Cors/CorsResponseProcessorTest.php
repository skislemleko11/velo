<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\Cors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Middlewares\Cors\CorsConfig\CorsConfig;
use Velo\Middlewares\Cors\CorsRequestHeaderName;
use Velo\Middlewares\Cors\CorsResponseHeaderName;
use Velo\Middlewares\Cors\CorsResponseProcessor;

final class CorsResponseProcessorTest extends TestCase
{
    private const string VARY_HEADER = 'vary';

    #[Test]
    #[DataProvider('preflightResponseProvider')]
    public function it_builds_preflight_response(CorsConfig $config, string $origin, array $expectedHeaders): void
    {
        $processor = new CorsResponseProcessor($config, $origin);

        $response = $processor->buildPreflightResponse();

        self::assertInstanceOf(NoContentResponse::class, $response);

        foreach ($expectedHeaders as $key => $value) {
            self::assertSame($value, $response->getHeader($key));
        }
    }

    public static function preflightResponseProvider(): array
    {
        return [
            [
                new CorsConfig(
                    allowedOrigins: ['hehe'],
                    allowedMethods: [RequestMethod::POST, RequestMethod::OPTIONS],
                    allowedHeaders: [],
                    exposedHeaders: [],
                    allowCredentials: false,
                    maxAgeSeconds: 0
                ),
                'hehe',
                [
                    CorsResponseHeaderName::ALLOW_ORIGIN->value => 'hehe',
                    CorsResponseHeaderName::ALLOW_METHODS->value => RequestMethod::POST->value . ', ' . RequestMethod::OPTIONS->value,
                    CorsResponseHeaderName::ALLOW_HEADERS->value => null,
                    CorsResponseHeaderName::ALLOW_CREDENTIALS->value => null,
                    CorsResponseHeaderName::MAX_AGE->value => '0',
                    CorsResponseHeaderName::EXPOSE_HEADERS->value => null,
                    self::VARY_HEADER => CorsRequestHeaderName::ORIGIN->value . ', ' . CorsRequestHeaderName::REQUEST_METHOD->value
                ]
            ],
            [
                new CorsConfig(
                    allowedOrigins: ['hehe'],
                    allowedMethods: [RequestMethod::POST, RequestMethod::GET, RequestMethod::OPTIONS],
                    allowedHeaders: ['Content-Type'],
                    exposedHeaders: ['Aa'],
                    allowCredentials: true,
                    maxAgeSeconds: 12
                ),
                'hehe',
                [
                    CorsResponseHeaderName::ALLOW_ORIGIN->value => 'hehe',
                    CorsResponseHeaderName::ALLOW_METHODS->value => RequestMethod::POST->value . ', ' . RequestMethod::GET->value . ', ' . RequestMethod::OPTIONS->value,
                    CorsResponseHeaderName::ALLOW_HEADERS->value => 'content-type',
                    CorsResponseHeaderName::ALLOW_CREDENTIALS->value => 'true',
                    CorsResponseHeaderName::MAX_AGE->value => '12',
                    CorsResponseHeaderName::EXPOSE_HEADERS->value => null,
                    self::VARY_HEADER => CorsRequestHeaderName::ORIGIN->value . ', ' . CorsRequestHeaderName::REQUEST_METHOD->value . ', ' . CorsRequestHeaderName::REQUEST_HEADERS->value
                ]
            ],
            [
                new CorsConfig(
                    allowedOrigins: ['*'],
                    allowedMethods: [RequestMethod::QUERY],
                    allowedHeaders: ['content-Type', 'accept', 'authorization'],
                    exposedHeaders: ['Aa'],
                    allowCredentials: false,
                    maxAgeSeconds: 20
                ),
                'hehe',
                [
                    CorsResponseHeaderName::ALLOW_ORIGIN->value => '*',
                    CorsResponseHeaderName::ALLOW_METHODS->value => RequestMethod::QUERY->value,
                    CorsResponseHeaderName::ALLOW_HEADERS->value => 'content-type, accept, authorization',
                    CorsResponseHeaderName::ALLOW_CREDENTIALS->value => null,
                    CorsResponseHeaderName::MAX_AGE->value => '20',
                    CorsResponseHeaderName::EXPOSE_HEADERS->value => null,
                    self::VARY_HEADER => CorsRequestHeaderName::REQUEST_METHOD->value . ', ' . CorsRequestHeaderName::REQUEST_HEADERS->value
                ]
            ]
        ];
    }

    #[Test]
    #[DataProvider('addCorsHeadersProvider')]
    public function it_adds_cors_headers(CorsConfig $config, string $origin, array $expectedHeaders): void
    {
        $processor = new CorsResponseProcessor($config, $origin);

        $response = new NoContentResponse();

        self::assertSame($response, $processor->addCorsHeaders($response));

        foreach ($expectedHeaders as $key => $value) {
            self::assertSame($value, $response->getHeader($key));
        }
    }

    #[Test]
    public function it_preserves_existing_vary_header_when_adding_cors_headers(): void
    {
        $processor = new CorsResponseProcessor(
            new CorsConfig(
                allowedOrigins: ['https://example.com'],
                exposedHeaders: ['X-Trace-Id'],
            ),
            'https://example.com'
        );

        $response = new NoContentResponse(200, [self::VARY_HEADER => 'accept-language']);

        $processor->addCorsHeaders($response);

        self::assertSame('accept-language, origin', $response->getHeader(self::VARY_HEADER));
        self::assertSame('https://example.com', $response->getHeader(CorsResponseHeaderName::ALLOW_ORIGIN->value));
        self::assertSame('x-trace-id', $response->getHeader(CorsResponseHeaderName::EXPOSE_HEADERS->value));
    }

    public static function addCorsHeadersProvider(): array
    {
        return [
            [
                new CorsConfig(
                    allowedOrigins: ['hehe'],
                    allowedMethods: [RequestMethod::POST, RequestMethod::OPTIONS],
                    allowedHeaders: ['a'],
                    exposedHeaders: [],
                    allowCredentials: false,
                    maxAgeSeconds: 20
                ),
                'hehe',
                [
                    CorsResponseHeaderName::ALLOW_ORIGIN->value => 'hehe',
                    CorsResponseHeaderName::ALLOW_METHODS->value => null,
                    CorsResponseHeaderName::ALLOW_HEADERS->value => null,
                    CorsResponseHeaderName::ALLOW_CREDENTIALS->value => null,
                    CorsResponseHeaderName::MAX_AGE->value => null,
                    CorsResponseHeaderName::EXPOSE_HEADERS->value => null,
                    self::VARY_HEADER => CorsRequestHeaderName::ORIGIN->value
                ]
            ],
            [
                new CorsConfig(
                    allowedOrigins: ['hehe'],
                    allowedMethods: [],
                    allowedHeaders: ['a'],
                    exposedHeaders: ['Hehe'],
                    allowCredentials: true,
                    maxAgeSeconds: 20
                ),
                'hehe',
                [
                    CorsResponseHeaderName::ALLOW_ORIGIN->value => 'hehe',
                    CorsResponseHeaderName::ALLOW_METHODS->value => null,
                    CorsResponseHeaderName::ALLOW_HEADERS->value => null,
                    CorsResponseHeaderName::ALLOW_CREDENTIALS->value => 'true',
                    CorsResponseHeaderName::MAX_AGE->value => null,
                    CorsResponseHeaderName::EXPOSE_HEADERS->value => 'hehe',
                    self::VARY_HEADER => CorsRequestHeaderName::ORIGIN->value
                ]
            ],
            [
                new CorsConfig(
                    allowedOrigins: ['*'],
                    allowedMethods: [RequestMethod::POST, RequestMethod::OPTIONS],
                    allowedHeaders: ['a'],
                    exposedHeaders: ['a', 'b', 'c'],
                    allowCredentials: false,
                    maxAgeSeconds: 20
                ),
                'hehe',
                [
                    CorsResponseHeaderName::ALLOW_ORIGIN->value => '*',
                    CorsResponseHeaderName::ALLOW_METHODS->value => null,
                    CorsResponseHeaderName::ALLOW_HEADERS->value => null,
                    CorsResponseHeaderName::ALLOW_CREDENTIALS->value => null,
                    CorsResponseHeaderName::MAX_AGE->value => null,
                    CorsResponseHeaderName::EXPOSE_HEADERS->value => 'a, b, c',
                    self::VARY_HEADER => null
                ]
            ]
        ];
    }
}