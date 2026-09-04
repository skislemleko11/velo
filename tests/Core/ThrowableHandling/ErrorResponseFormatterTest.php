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

    private function getProperty(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);

        return $reflection->getProperty($property)->getValue($object);
    }

    #[Test]
    #[DataProvider('json_and_plain_text_dataProvider')]
    public function it_formats_json_response_with_status_code_message_and_headers($exception, $message, $statusCode, $headers)
    {
        $response = $this->formatter->formatJson($exception);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($statusCode, $response->statusCode);
        self::assertSame(
            [
                'error' => [
                    'statusCode' => $statusCode,
                    'message' => $message,
                ]
            ],
            $this->getProperty($response, 'body')
        );

        self::assertEquals(
            $headers + ['content-type' => 'application/json'],
            $response->getHeaders()
        );
    }

    #[Test]
    #[DataProvider('json_and_plain_text_dataProvider')]
    public function it_formats_plain_text_response_with_status_code_and_headers($exception, $message, $statusCode, $headers)
    {
        $response = $this->formatter->formatPlainText($exception);

        self::assertInstanceOf(TextResponse::class, $response);
        self::assertSame($statusCode, $response->statusCode);
        self::assertSame(
            $message,
            $this->getProperty($response, 'content')
        );
        self::assertEquals(
            $headers + ['content-type' => 'text/plain; charset=utf-8'],
            $response->getHeaders()
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
    public function it_formats_view_response_using_status_code_error_view(): void
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
            ->method('resolveErrorFilePath')
            ->with('403')
            ->willReturn('/views/error403.php');

        $response = $this->formatter->formatView($exception);

        self::assertInstanceOf(ViewResponse::class, $response);
        self::assertSame(403, $response->statusCode);
        self::assertSame(
            '/views/error403.php',
            $this->getProperty($response, 'relativeToViewsDirFilePath')
        );
        self::assertEquals(
            ['content-type' => 'text/html; charset=utf-8', 'gg' => 'gg'],
            $response->getHeaders()
        );
    }

    #[Test]
    public function it_formats_plain_text_response_when_no_error_view_exists(): void
    {
        $formatter = $this->getMockBuilder(ErrorResponseFormatter::class)
            ->setConstructorArgs([$this->pathResolver])
            ->onlyMethods(['formatPlainText'])
            ->getMock();

        $exception = new Exception();

        $formatter->expects($this->once())
            ->method('formatPlainText')
            ->with($exception);

        $this->pathResolver
            ->expects($this->once())
            ->method('resolveErrorFilePath')
            ->with(500)
            ->willReturn(false);

        $formatter->formatView($exception);
    }
}