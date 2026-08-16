<?php

declare(strict_types=1);

namespace Velo\Tests\Core\ThrowableHandling;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter;
use Velo\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Exceptions\Interfaces\HttpExceptionWithHeadersInterface;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\HttpResponse;

#[AllowMockObjectsWithoutExpectations]
final class ErrorResponseFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_generic_throwable_as_json_with_500_status_and_default_message(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new Exception('Something went wrong');

        $response = $formatter->formatJson($throwable);

        $this->assertSame(500, $response->statusCode);
        $this->assertSame(
            [
                'error' => [
                    'statusCode' => 500,
                    'message' => ErrorResponseFormatter::DEFAULT_ERROR_MESSAGE
                ],
            ],
            $response->data
        );
        $this->assertSame(
            ['Content-Type' => 'application/json'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_formats_http_exception_as_json_with_status_code_and_message(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new class extends Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 404;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }
        };

        $response = $formatter->formatJson($throwable);

        $this->assertSame(404, $response->statusCode);
        $this->assertSame(
            [
                'error' => [
                    'statusCode' => 404,
                    'message' => 'hehe',
                ],
            ],
            $response->data
        );
        $this->assertSame(
            ['Content-Type' => 'application/json'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_formats_http_exception_with_headers_as_json_with_status_code_message_and_headers(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new class extends Exception implements HttpExceptionWithHeadersInterface {
            public function getStatusCode(): int
            {
                return 333;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }

            public function getHeaders(): array
            {
                return ['X-Custom-Header' => 'Custom Value'];
            }
        };

        $response = $formatter->formatJson($throwable);

        $this->assertSame(333, $response->statusCode);
        $this->assertSame(
            [
                'error' => [
                    'statusCode' => 333,
                    'message' => 'hehe'
                ],
            ],
            $response->data
        );
        $this->assertEquals(
            ['Content-Type' => 'application/json', 'X-Custom-Header' => 'Custom Value'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }


    #[Test]
    public function it_formats_generic_throwable_as_plain_text_with_500_status_and_default_message(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new Exception('Something went wrong');

        $response = $formatter->formatPlainText($throwable);

        $this->assertSame(500, $response->statusCode);
        $this->assertSame(ErrorResponseFormatter::DEFAULT_ERROR_MESSAGE, $response->data);
        $this->assertSame(
            ['Content-Type' => 'text/plain'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_formats_http_exception_as_plain_text_with_status_code_and_message(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new class extends Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 403;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }
        };

        $response = $formatter->formatPlainText($throwable);

        $this->assertSame(403, $response->statusCode);
        $this->assertSame('hehe', $response->data);
        $this->assertEquals(
            ['Content-Type' => 'text/plain'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_formats_http_exception_with_headers_as_plain_text_with_status_code_message_and_headers(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new class extends Exception implements HttpExceptionWithHeadersInterface {
            public function getStatusCode(): int
            {
                return 403;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }

            public function getHeaders(): array
            {
                return ['X-Custom-Header' => 'Custom Value'];
            }
        };

        $response = $formatter->formatPlainText($throwable);

        $this->assertSame(403, $response->statusCode);
        $this->assertSame('hehe', $response->data);
        $this->assertEquals(
            ['Content-Type' => 'text/plain', 'X-Custom-Header' => 'Custom Value'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_uses_status_specific_view_when_it_is_registered(): void
    {
        $pathResolver = $this->createMock(PathResolver::class);

        $throwable = new class extends Exception implements HttpExceptionWithHeadersInterface {
            public function getStatusCode(): int
            {
                return 404;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }

            public function getHeaders(): array
            {
                return ['X-Custom-Header' => 'Custom Value'];
            }
        };

        $pathResolver
            ->expects($this->once())
            ->method('isFileRegistered')
            ->with('error404')
            ->willReturn(true);

        $pathResolver
            ->expects($this->once())
            ->method('getFilePath')
            ->with('error404')
            ->willReturn('/views/error404.php');

        $formatter = new ErrorResponseFormatter($pathResolver);

        $response = $formatter->formatView($throwable);

        $this->assertSame(404, $response->statusCode);
        $this->assertSame('/views/error404.php', $response->viewPath);
        $this->assertSame([], $response->data);
        $this->assertEquals(['Content-Type' => 'text/html; charset=utf-8', 'X-Custom-Header' => 'Custom Value'], $response->headers);
    }

    #[Test]
    public function it_uses_generic_error_view_when_status_specific_view_is_not_registered(): void
    {
        $pathResolver = $this->createMock(PathResolver::class);

        $throwable = new class extends Exception implements HttpExceptionWithHeadersInterface {
            public function getStatusCode(): int
            {
                return 404;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }

            public function getHeaders(): array
            {
                return ['X-Custom-Header' => 'Custom Value'];
            }
        };

        $pathResolver
            ->expects($this->exactly(2))
            ->method('isFileRegistered')
            ->willReturnMap([
                ['error404', false],
                ['error', true],
            ]);

        $pathResolver
            ->expects($this->once())
            ->method('getFilePath')
            ->with('error')
            ->willReturn('/views/error.php');

        $formatter = new ErrorResponseFormatter($pathResolver);

        $response = $formatter->formatView($throwable);

        $this->assertSame(404, $response->statusCode);
        $this->assertSame('/views/error.php', $response->viewPath);
        $this->assertSame([], $response->data);
        $this->assertEquals(['Content-Type' => 'text/html; charset=utf-8', 'X-Custom-Header' => 'Custom Value'], $response->headers);
    }

    #[Test]
    public function it_falls_back_to_plain_text_when_no_error_view_is_registered(): void
    {
        $pathResolver = $this->createMock(PathResolver::class);

        $throwable = new class extends Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 500;
            }

            public function shouldLogException(): bool
            {
                return false;
            }

            public function getPublicMessage(): string
            {
                return 'An error occurred';
            }
        };

        $pathResolver
            ->expects($this->exactly(2))
            ->method('isFileRegistered')
            ->willReturnMap([
                ['error500', false],
                ['error', false],
            ]);

        $pathResolver
            ->expects($this->never())
            ->method('getFilePath');

        $formatter = $this->getMockBuilder(ErrorResponseFormatter::class)
            ->setConstructorArgs([$pathResolver])
            ->onlyMethods(['formatPlainText'])
            ->getMock();

        $expectedResult = HttpResponse::plainText('yup');

        $formatter->expects($this->once())
            ->method('formatPlainText')
            ->with($throwable)
            ->willReturn($expectedResult);

        $response = $formatter->formatView($throwable);

        $this->assertSame($expectedResult, $response);
    }
}