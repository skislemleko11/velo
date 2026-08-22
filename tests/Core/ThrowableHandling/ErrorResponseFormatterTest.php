<?php

declare(strict_types=1);

namespace Velo\Tests\Core\ThrowableHandling;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\ErrorResponseFormatter;
use Velo\Exceptions\Interfaces\HttpResponseExceptionInterface;
use Velo\Exceptions\Interfaces\HttpResponseExceptionWithHeadersInterface;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\TextResponse;
use Velo\Http\Responses\Concrete\ViewResponse;

#[AllowMockObjectsWithoutExpectations]
final class ErrorResponseFormatterTest extends TestCase
{
    private ErrorResponseFormatter $formatter;
    private PathResolver $pathResolver;

    protected function setUp(): void
    {
        $this->pathResolver = $this->createMock(PathResolver::class);
        $this->formatter = new ErrorResponseFormatter($this->pathResolver);
    }

    private function getProperty(object $object, string $property)
    {
        $reflection = new ReflectionClass($object);

        return $reflection->getProperty($property)->getValue($object);
    }

    #[Test]
    #[DataProvider('json_and_plain_text_dataProvider')]
    public function it_formats_json_response_with_status_code_message_and_headers($exception, $message, $statusCode, $headers)
    {
        $response = $this->formatter->formatJson($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($statusCode, $response->statusCode);
        $this->assertSame(
            [
                'error' => [
                    'statusCode' => $statusCode,
                    'message' => $message,
                ]
            ],
            $this->getProperty($response, 'body')
        );
        $this->assertEquals(
            $headers + ['Content-Type' => 'application/json'],
            $this->getProperty($response, 'headers')
        );
    }

    #[Test]
    #[DataProvider('json_and_plain_text_dataProvider')]
    public function it_formats_plain_text_response_with_status_code_and_headers($exception, $message, $statusCode, $headers)
    {
        $response = $this->formatter->formatPlainText($exception);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame($statusCode, $response->statusCode);
        $this->assertSame(
            $message,
            $this->getProperty($response, 'content')
        );
        $this->assertEquals(
            $headers + ['Content-Type' => 'text/plain; charset=utf-8'],
            $this->getProperty($response, 'headers')
        );
    }

    public static function json_and_plain_text_dataProvider(): array
    {
        return [
            [
                new Exception(),
                ErrorResponseFormatter::DEFAULT_ERROR_MESSAGE,
                500,
                []
            ],
            [
                new class() extends Exception implements HttpResponseExceptionInterface {

                    public function getStatusCode(): int
                    {
                        return 502;
                    }

                    public function shouldLogException(): bool
                    {
                        return true;
                    }

                    public function getPublicMessage(): string
                    {
                        return 'hehe';
                    }
                },
                'hehe',
                502,
                []
            ],
            [
                new class() extends Exception implements HttpResponseExceptionWithHeadersInterface {

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
                        return ['gg' => 'gg'];
                    }
                },
                'hehe',
                403,
                ['gg' => 'gg']
            ]
        ];
    }

    #[Test]
    public function it_formats_view_response_using_status_code_specific_view(): void
    {
        $exception = new class() extends Exception implements HttpResponseExceptionWithHeadersInterface {
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
                return ['gg' => 'gg'];
            }
        };

        $this->pathResolver
            ->expects($this->once())
            ->method('isFileRegistered')
            ->with('error403')
            ->willReturn(true);

        $this->pathResolver
            ->expects($this->once())
            ->method('getFilePath')
            ->with('error403')
            ->willReturn('/views/error403.php');

        $response = $this->formatter->formatView($exception);

        $this->assertInstanceOf(ViewResponse::class, $response);
        $this->assertSame(403, $response->statusCode);
        $this->assertSame(
            '/views/error403.php',
            $this->getProperty($response, 'relativeToViewsDirFilePath')
        );
        $this->assertEquals(
            ['Content-Type' => 'text/html; charset=utf-8', 'gg' => 'gg'],
            $this->getProperty($response, 'headers')
        );
    }

    #[Test]
    public function it_formats_view_response_using_generic_error_view_when_specific_view_does_not_exist(): void
    {
        $exception = new Exception();

        $this->pathResolver
            ->expects($this->exactly(2))
            ->method('isFileRegistered')
            ->willReturnMap([
                ['error500', false],
                ['error', true],
            ]);

        $this->pathResolver
            ->expects($this->once())
            ->method('getFilePath')
            ->with('error')
            ->willReturn('/views/error.php');

        $response = $this->formatter->formatView($exception);

        $this->assertInstanceOf(ViewResponse::class, $response);
        $this->assertSame(500, $response->statusCode);
        $this->assertSame(
            '/views/error.php',
            $this->getProperty($response, 'relativeToViewsDirFilePath')
        );
        $this->assertEquals(
            ['Content-Type' => 'text/html; charset=utf-8'],
            $this->getProperty($response, 'headers')
        );
    }

    #[Test]
    public function it_formats_plain_text_response_when_no_error_view_exists(): void
    {
        $exception = new Exception();

        $this->pathResolver
            ->expects($this->exactly(2))
            ->method('isFileRegistered')
            ->willReturnMap([
                ['error500', false],
                ['error', false],
            ]);

        $this->pathResolver
            ->expects($this->never())
            ->method('getFilePath');

        $response = $this->formatter->formatView($exception);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame(500, $response->statusCode);
        $this->assertSame(
            ErrorResponseFormatter::DEFAULT_ERROR_MESSAGE,
            $this->getProperty($response, 'content')
        );
        $this->assertEquals(
            ['Content-Type' => 'text/plain; charset=utf-8'],
            $this->getProperty($response, 'headers')
        );
    }
}