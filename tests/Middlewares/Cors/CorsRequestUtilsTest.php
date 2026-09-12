<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\Cors;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RequestMethod;
use Velo\Middlewares\Cors\CorsConfig;
use Velo\Middlewares\Cors\CorsRequestUtils;
use Velo\Middlewares\Cors\Headers\CorsRequestHeaderName;

final class CorsRequestUtilsTest extends TestCase
{
    #[Test]
    public function it_returns_request_origin(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::GET,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
            ],
        );

        self::assertSame(
            'https://example.com',
            CorsRequestUtils::getRequestOrigin($request),
        );
    }

    #[Test]
    public function it_returns_null_when_request_origin_is_missing(): void
    {
        $request = CorsTestsUtils::createRequest(RequestMethod::OPTIONS);

        self::assertNull(
            CorsRequestUtils::getRequestOrigin($request),
        );
    }

    #[Test]
    public function it_detects_preflight_request(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflight($request),
        );
    }

    #[Test]
    public function it_does_not_detect_preflight_request_when_method_is_not_options(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::POST,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflight($request),
        );
    }

    #[Test]
    public function it_does_not_detect_preflight_request_when_origin_is_missing(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflight($request),
        );
    }

    #[Test]
    public function it_does_not_detect_preflight_request_when_requested_method_is_missing(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflight($request),
        );
    }

    #[Test]
    public function it_allows_configured_origin(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
        );

        self::assertTrue(
            CorsRequestUtils::isOriginAllowed(
                'https://example.com',
                $config,
            ),
        );
    }

    #[Test]
    public function it_rejects_unconfigured_origin(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
        );

        self::assertFalse(
            CorsRequestUtils::isOriginAllowed(
                'https://other.example.com',
                $config,
            ),
        );
    }

    #[Test]
    public function it_rejects_null_origin(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
        );

        self::assertFalse(
            CorsRequestUtils::isOriginAllowed(
                null,
                $config,
            ),
        );
    }

    #[Test]
    public function it_allows_any_origin_when_wildcard_is_configured(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['*'],
        );

        self::assertTrue(
            CorsRequestUtils::isOriginAllowed(
                'https://any.example.com',
                $config,
            ),
        );
    }

    #[Test]
    public function it_allows_configured_method(): void
    {
        self::assertTrue(
            CorsRequestUtils::isMethodAllowed(
                RequestMethod::POST,
                [
                    RequestMethod::GET,
                    RequestMethod::POST,
                ],
            ),
        );
    }

    #[Test]
    public function it_rejects_unconfigured_method(): void
    {
        self::assertFalse(
            CorsRequestUtils::isMethodAllowed(
                RequestMethod::DELETE,
                [
                    RequestMethod::GET,
                    RequestMethod::POST,
                ],
            ),
        );
    }

    #[Test]
    public function it_allows_preflight_request_with_allowed_origin_and_method(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_rejects_preflight_request_with_unknown_method(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'INVALID',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_rejects_preflight_request_with_missing_method(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_rejects_preflight_request_with_disallowed_origin(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://evil.example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_rejects_preflight_request_with_disallowed_method(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::GET],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_allows_preflight_request_without_requested_headers(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
            allowedHeaders: ['content-type'],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_allows_preflight_request_with_allowed_headers(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
            allowedHeaders: [
                'content-type',
                'authorization',
            ],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
                CorsRequestHeaderName::REQUEST_HEADERS->value =>
                    'Content-Type, Authorization',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_normalizes_requested_headers_before_checking_them(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
            allowedHeaders: [
                'Content-Type',
                ' Authorization ',
            ],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
                CorsRequestHeaderName::REQUEST_HEADERS->value =>
                    ' content-type , AUTHORIZATION ',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_rejects_preflight_request_with_disallowed_header(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
            allowedHeaders: ['content-type'],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
                CorsRequestHeaderName::REQUEST_HEADERS->value =>
                    'Content-Type, Authorization',
            ],
        );

        self::assertFalse(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_allows_any_requested_header_when_wildcard_is_configured(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['https://example.com'],
            allowedMethods: [RequestMethod::POST],
            allowedHeaders: ['*'],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
                CorsRequestHeaderName::REQUEST_HEADERS->value =>
                    'X-Custom-Header, Authorization',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }

    #[Test]
    public function it_allows_preflight_request_from_any_origin_with_wildcard(): void
    {
        $config = new CorsConfig(
            allowedOrigins: ['*'],
            allowedMethods: [RequestMethod::POST],
        );

        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertTrue(
            CorsRequestUtils::isPreflightRequestAllowed(
                $config,
                $request,
            ),
        );
    }
}