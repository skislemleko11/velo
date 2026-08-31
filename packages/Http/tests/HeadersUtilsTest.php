<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\HeadersUtils;

class HeadersUtilsTest extends TestCase
{
    #[Test]
    public function it_makes_string_lowercase_and_trimmed()
    {
        self::assertEquals('hello world', HeadersUtils::makeLowerCaseAndTrim('  Hello World  '));
        self::assertEquals('a', HeadersUtils::makeLowerCaseAndTrim("\t  A         \t"));
        self::assertEquals('d', HeadersUtils::makeLowerCaseAndTrim("\n d  \n\n"));
    }

    #[Test]
    public function it_gets_headers_from_server_superglobal(): void
    {
        $server = $_SERVER;

        $_SERVER = [
            'HTTP_HOST' => 'example.com',
            'HTTP_CONTENT_TYPE' => ' application/json ',
            'HTTP_X_CUSTOM_HEADER' => ' custom value ',
            'SOME_OTHER_VALUE' => 'should be ignored',
            'SERVER_NAME' => 'example.com',
        ];

        try {
            self::assertSame([
                'host' => 'example.com',
                'content-type' => ' application/json ',
                'x-custom-header' => ' custom value ',
            ], HeadersUtils::getHeadersFromServerSuperGlobal());
        } finally {
            $_SERVER = $server;
        }
    }

}