<?php

declare(strict_types=1);

namespace Velo\Tests\Core\ThrowableHandling;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Router\Exceptions\Interfaces\HttpExceptionInterface;

#[AllowMockObjectsWithoutExpectations]
final class ErrorResponseFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_generic_throwable_as_json_with_500_status(): void
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
                    'message' => 'An error occurred',
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
    public function it_formats_http_exception_as_json_with_exception_status(): void
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
        };

        $response = $formatter->formatJson($throwable);

        $this->assertSame(404, $response->statusCode);
        $this->assertSame(
            [
                'error' => [
                    'statusCode' => 404,
                    'message' => 'An error occurred',
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
    public function it_formats_generic_throwable_as_plain_text_with_500_status(): void
    {
        $pathResolver = $this->createStub(PathResolver::class);
        $formatter = new ErrorResponseFormatter($pathResolver);

        $throwable = new Exception('Something went wrong');

        $response = $formatter->formatPlainText($throwable);

        $this->assertSame(500, $response->statusCode);
        $this->assertSame('An error occurred', $response->data);
        $this->assertSame(
            ['Content-Type' => 'text/plain'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_formats_http_exception_as_plain_text_with_exception_status(): void
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
        };

        $response = $formatter->formatPlainText($throwable);

        $this->assertSame(403, $response->statusCode);
        $this->assertSame('An error occurred', $response->data);
        $this->assertSame(
            ['Content-Type' => 'text/plain'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }

    #[Test]
    public function it_uses_status_specific_view_when_it_is_registered(): void
    {
        $pathResolver = $this->createMock(PathResolver::class);

        $throwable = new class extends Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 404;
            }

            public function shouldLogException(): bool
            {
                return false;
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
        $this->assertSame([], $response->headers);
    }

    #[Test]
    public function it_uses_generic_error_view_when_status_specific_view_is_not_registered(): void
    {
        $pathResolver = $this->createMock(PathResolver::class);

        $throwable = new class extends Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 404;
            }

            public function shouldLogException(): bool
            {
                return false;
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
        $this->assertSame([], $response->headers);
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

        $formatter = new ErrorResponseFormatter($pathResolver);

        $response = $formatter->formatView($throwable);

        $this->assertSame(500, $response->statusCode);
        $this->assertSame('An error occurred', $response->data);
        $this->assertSame(
            ['Content-Type' => 'text/plain'],
            $response->headers
        );
        $this->assertNull($response->viewPath);
    }
}