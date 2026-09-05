<?php

declare(strict_types=1);

namespace Velo\Tests\Core\ThrowableHandling;

use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\ErrorResponseFormatter;
use Velo\Core\ThrowableHandling\ThrowableHandler;
use Velo\Exceptions\Interfaces\HttpResponseExceptionInterface;
use Velo\Http\ResponseRenderer;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\TextResponse;
use Velo\Http\Responses\Concrete\ViewResponse;

#[AllowMockObjectsWithoutExpectations]
final class ThrowableHandlerTest extends TestCase
{
    private int $originalErrorReporting;
    private LoggerInterface&MockObject $loggerMock;
    private ResponseRenderer&MockObject $responseRendererMock;
    private ErrorResponseFormatter&MockObject $errorResponseFormatterMock;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting();

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->responseRendererMock = $this->createMock(ResponseRenderer::class);
        $this->errorResponseFormatterMock = $this->createMock(ErrorResponseFormatter::class);

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
        $exception = new ErrorException(
            'boom',
            0,
            E_USER_ERROR,
            __FILE__,
            __LINE__
        );

        $response = self::createStub(JsonResponse::class);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(self::identicalTo($exception));

        $this->loggerMock
            ->expects($this->never())
            ->method('critical');

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatJson')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_HttpException_and_logs_it_when_should_log_is_true(): void
    {
        $exception = new class('boom') extends Exception implements HttpResponseExceptionInterface {
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

        $response = self::createStub(JsonResponse::class);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(self::identicalTo($exception));

        $this->loggerMock
            ->expects($this->never())
            ->method('critical');

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatJson')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_HttpException_without_logging_when_should_log_is_false(): void
    {
        $exception = new class('not found') extends Exception implements HttpResponseExceptionInterface {
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

        $response = self::createStub(JsonResponse::class);

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->loggerMock
            ->expects($this->never())
            ->method('critical');

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatJson')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_generic_throwable_as_critical(): void
    {
        $exception = new Exception('something went wrong');

        $response = self::createStub(JsonResponse::class);

        $this->loggerMock
            ->expects($this->once())
            ->method('critical')
            ->with(self::identicalTo($exception));

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatJson')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_html_response_when_html_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $exception = new Exception('boom');
        $response = self::createStub(ViewResponse::class);

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatView')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->errorResponseFormatterMock
            ->expects($this->never())
            ->method('formatPlainText');

        $this->errorResponseFormatterMock
            ->expects($this->never())
            ->method('formatJson');

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_plain_text_response_when_plain_text_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';

        $exception = new Exception('boom');
        $response = self::createStub(TextResponse::class);

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatPlainText')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->errorResponseFormatterMock
            ->expects($this->never())
            ->method('formatView');

        $this->errorResponseFormatterMock
            ->expects($this->never())
            ->method('formatJson');

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_json_response_by_default(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);

        $exception = new Exception('boom');
        $response = self::createStub(JsonResponse::class);

        $this->errorResponseFormatterMock
            ->expects($this->once())
            ->method('formatJson')
            ->with(self::identicalTo($exception))
            ->willReturn($response);

        $this->errorResponseFormatterMock
            ->expects($this->never())
            ->method('formatView');

        $this->errorResponseFormatterMock
            ->expects($this->never())
            ->method('formatPlainText');

        $this->responseRendererMock
            ->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_returns_false_when_error_reporting_is_disabled(): void
    {
        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        error_reporting(0);

        $result = $handler->throwErrorException(
            E_USER_NOTICE,
            'msg',
            __FILE__,
            __LINE__
        );

        self::assertFalse($result);
    }

    #[Test]
    public function it_throws_ErrorException_when_error_reporting_is_enabled(): void
    {
        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
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

    #[Test]
    public function it_sets_exception_and_error_global_handler_to_its_methods(): void
    {
        $handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

        $handler->setAsGlobalExceptionAndErrorHandler();

        self::assertEquals([$handler, 'handleThrowable'], get_exception_handler());
        self::assertEquals([$handler, 'throwErrorException'], get_error_handler());

        set_exception_handler(null);
        set_error_handler(null);
    }
}