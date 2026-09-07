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
use Velo\Http\Responses\Response;
use Velo\Middlewares\Exceptions\ThrowableResponseActionException;

#[AllowMockObjectsWithoutExpectations]
final class ThrowableHandlerTest extends TestCase
{
    private int $originalErrorReporting;
    private LoggerInterface&MockObject $loggerMock;
    private ResponseRenderer&MockObject $responseRendererMock;
    private ErrorResponseFormatter&MockObject $errorResponseFormatterMock;
    private ThrowableHandler $handler;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting();

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->responseRendererMock = $this->createMock(ResponseRenderer::class);
        $this->errorResponseFormatterMock = $this->createMock(ErrorResponseFormatter::class);
        $this->handler = new ThrowableHandler(
            $this->loggerMock,
            $this->responseRendererMock,
            $this->errorResponseFormatterMock
        );

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

        $response = new JsonResponse('');

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

        $this->handler->handleThrowable($exception);
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

        $response = new JsonResponse('');

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

        $this->handler->handleThrowable($exception);
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

        $response = new JsonResponse('');

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

        $this->handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_generic_throwable_as_critical(): void
    {
        $exception = new Exception('something went wrong');

        $response = new JsonResponse('');

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

        $this->handler->handleThrowable($exception);
    }

    #[Test]
    public function it_uses_the_wrapped_throwable_for_logging_semantics_when_action_wrappers_are_present(): void
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

        $response = new JsonResponse('');

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

        $actionException = new class($exception) extends ThrowableResponseActionException {
            protected function executeAction(Response $response): Response
            {
                return $response;
            }
        };

        $this->handler->handleThrowable($actionException);
    }

    #[Test]
    public function it_formats_html_response_when_html_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $exception = new Exception('boom');
        $response = new ViewResponse('');

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

        $this->handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_plain_text_response_when_plain_text_is_requested(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';

        $exception = new Exception('boom');
        $response = new TextResponse('a');

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

        $this->handler->handleThrowable($exception);
    }

    #[Test]
    public function it_formats_json_response_by_default(): void
    {
        $exception = new Exception('boom');
        $response = new JsonResponse('');

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

        $this->handler->handleThrowable($exception);
    }

    #[Test]
    public function it_returns_false_when_error_reporting_is_disabled(): void
    {
        error_reporting(0);

        $result = $this->handler->throwErrorException(
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
        error_reporting(E_ALL);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageIs('msg');

        $this->handler->throwErrorException(
            E_USER_NOTICE,
            'msg',
            __FILE__,
            __LINE__
        );
    }

    #[Test]
    public function it_sets_exception_and_error_global_handler_to_its_methods(): void
    {
        $previousExceptionHandler = get_exception_handler();
        $previousErrorHandler = get_error_handler();

        $this->handler->setAsGlobalExceptionAndErrorHandler();

        self::assertEquals([$this->handler, 'handleThrowable'], get_exception_handler());
        self::assertEquals([$this->handler, 'throwErrorException'], get_error_handler());

        set_exception_handler($previousExceptionHandler);
        set_error_handler($previousErrorHandler);
    }

    #[Test]
    public function it_applies_throwables_actions_to_created_responses(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/plain';

        $baseException = new Exception();
        $response = new TextResponse('hehe');

        $this->errorResponseFormatterMock->expects($this->once())
            ->method('formatPlainText')
            ->with(self::identicalTo($baseException))
            ->willReturn($response);

        $this->responseRendererMock->expects($this->once())
            ->method('render')
            ->with(self::identicalTo($response));

        $actionException = new class($baseException) extends ThrowableResponseActionException {
            protected function executeAction(Response $response): Response
            {
                return $response->setHeader('a', 'a');
            }
        };

        $this->handler->handleThrowable($actionException);

        self::assertSame('a', $response->getHeader('a'));
    }
}