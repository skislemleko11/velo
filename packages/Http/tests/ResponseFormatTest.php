<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\ResponseFormat;

final class ResponseFormatTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);
    }

    #[Test]
    #[DataProvider('acceptHeaderProvider')]
    public function it_creates_response_format_from_accept_header(
        string         $acceptHeader,
        ResponseFormat $expectedFormat
    ): void
    {
        self::assertSame(
            $expectedFormat,
            ResponseFormat::fromGivenAcceptHeader($acceptHeader)
        );
    }

    public static function acceptHeaderProvider(): array
    {
        return [
            'html' => [
                'text/html',
                ResponseFormat::HTML,
            ],
            'json' => [
                'application/json',
                ResponseFormat::JSON,
            ],
            'plain text' => [
                'text/plain',
                ResponseFormat::PLAIN_TEXT,
            ],
            'empty header defaults to json' => [
                '',
                ResponseFormat::JSON,
            ],
            'unknown format defaults to json' => [
                'application/xml',
                ResponseFormat::JSON,
            ],
            'html with additional values' => [
                'text/html,application/json',
                ResponseFormat::HTML,
            ],
            'plain text with additional values' => [
                'application/json,text/plain',
                ResponseFormat::PLAIN_TEXT,
            ],
            'html takes priority over plain text' => [
                'text/plain,text/html',
                ResponseFormat::HTML,
            ],
            'html takes priority over json' => [
                'application/json,text/html',
                ResponseFormat::HTML,
            ],
            'plain text takes priority over json' => [
                'application/json,text/plain',
                ResponseFormat::PLAIN_TEXT,
            ],
        ];
    }

    #[Test]
    public function it_uses_accept_header_from_server_superglobal(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $format = ResponseFormat::fromGlobalAcceptHeader();

        self::assertSame(ResponseFormat::HTML, $format);
    }

    #[Test]
    public function it_defaults_to_json_when_no_accept_header_is_provided(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);

        $format = ResponseFormat::fromGlobalAcceptHeader();

        self::assertSame(ResponseFormat::JSON, $format);
    }

    #[Test]
    public function it_casts_accept_header_to_string(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 1;

        $format = ResponseFormat::fromGlobalAcceptHeader();

        self::assertSame(ResponseFormat::JSON, $format);
    }
}