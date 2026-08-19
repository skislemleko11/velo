<?php

declare(strict_types=1);

namespace Velo\Tests\Core\ThrowableHandling;

use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\ErrorResponseFormatter;
use Velo\Core\ThrowableHandling\ThrowableHandler;
use Velo\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;

#[AllowMockObjectsWithoutExpectations]
final class ThrowableHandlerTest extends TestCase
{
    private int $originalErrorReporting;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting();

        unset($_SERVER['HTTP_ACCEPT']);
    }

    protected function tearDown(): void
    {
        error_reporting($this->originalErrorReporting);

        unset($_SERVER['HTTP_ACCEPT']);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    #[Test]
    public function it_handles_ErrorException_logs_as_error_and_renders_response(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new ErrorException(
            'boom',
            0,
            E_USER_ERROR,
            __FILE__,
            __LINE__
        );

        $response = $this->createStub(HttpResponse::class);

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->identicalTo($exception));

        $logger
            ->expects($this->never())
            ->method('critical');

        $formatter
            ->expects($this->once())
            ->method('formatJson')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_HttpException_and_logs_it_when_should_log_is_true(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new class('boom') extends Exception implements HttpExceptionInterface {
            public function getStatusCode(): int
            {
                return 404;
            }

            public function shouldLogException(): bool
            {
                return true;
            }

            public function getPublicMessage(): string
            {
                return 'hehe';
            }
        };

        $response = $this->createStub(HttpResponse::class);

        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->identicalTo($exception));

        $logger
            ->expects($this->never())
            ->method('critical');

        $formatter
            ->expects($this->once())
            ->method('formatJson')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_HttpException_without_logging_when_should_log_is_false(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new class('not found') extends Exception implements HttpExceptionInterface {
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

        $response = $this->createStub(HttpResponse::class);

        $logger
            ->expects($this->never())
            ->method('error');

        $logger
            ->expects($this->never())
            ->method('critical');

        $formatter
            ->expects($this->once())
            ->method('formatJson')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_generic_throwable_as_critical(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new Exception('something went wrong');

        $response = $this->createStub(HttpResponse::class);

        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->identicalTo($exception));

        $logger
            ->expects($this->never())
            ->method('error');

        $formatter
            ->expects($this->once())
            ->method('formatJson')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_html_response_when_html_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $logger = $this->createStub(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new Exception('boom');
        $response = $this->createStub(HttpResponse::class);

        $formatter
            ->expects($this->once())
            ->method('formatView')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $formatter
            ->expects($this->never())
            ->method('formatPlainText');

        $formatter
            ->expects($this->never())
            ->method('formatJson');

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_plain_text_response_when_plain_text_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';

        $logger = $this->createStub(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new Exception('boom');
        $response = $this->createStub(HttpResponse::class);

        $formatter
            ->expects($this->once())
            ->method('formatPlainText')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $formatter
            ->expects($this->never())
            ->method('formatView');

        $formatter
            ->expects($this->never())
            ->method('formatJson');

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_json_response_by_default(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);

        $logger = $this->createStub(LoggerInterface::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);
        $formatter = $this->createMock(ErrorResponseFormatter::class);

        $exception = new Exception('boom');
        $response = $this->createStub(HttpResponse::class);

        $formatter
            ->expects($this->once())
            ->method('formatJson')
            ->with($this->identicalTo($exception))
            ->willReturn($response);

        $formatter
            ->expects($this->never())
            ->method('formatView');

        $formatter
            ->expects($this->never())
            ->method('formatPlainText');

        $responseRenderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($response));

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_returns_false_when_error_reporting_is_disabled(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $responseRenderer = $this->createStub(ResponseRenderer::class);
        $formatter = $this->createStub(ErrorResponseFormatter::class);

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        error_reporting(0);

        $result = $handler->throwErrorException(
            E_USER_NOTICE,
            'msg',
            __FILE__,
            __LINE__
        );

        $this->assertFalse($result);
    }

    #[Test]
    public function it_throws_ErrorException_when_error_reporting_is_enabled(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $responseRenderer = $this->createStub(ResponseRenderer::class);
        $formatter = $this->createStub(ErrorResponseFormatter::class);

        $handler = new ThrowableHandler(
            $logger,
            $responseRenderer,
            $formatter
        );

        error_reporting(E_ALL);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageIs('msg');

        $handler->throwErrorException(
            E_USER_NOTICE,
            'msg',
            __FILE__,
            __LINE__
        );
    }
}