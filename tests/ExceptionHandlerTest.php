<?php
declare(strict_types=1);

namespace Velo\Tests;

use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Core\ExceptionHandler;
use Velo\Http\HttpResponse;
use Velo\Logger\Logger;
use Velo\Router\PathResolver;
use Velo\Http\ResponseRenderer;
use Velo\Router\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Router\Exceptions\PageNotFoundException;

class ExceptionHandlerTest extends TestCase
{
    private int $originalErrorReporting;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting();
    }

    protected function tearDown(): void
    {
        error_reporting($this->originalErrorReporting);
    }

    #[Test]
    public function it_handles_ErrorException_logs_error_and_renders_500_response(): void
    {
        $logger = $this->createMock(Logger::class);
        $pathResolver = $this->createMock(PathResolver::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);

        $exception = new ErrorException('boom', 0, E_USER_ERROR, __FILE__, __LINE__);

        $pathResolver->expects($this->once())
            ->method('isFileRegistered')
            ->with('error500')
            ->willReturn(true);

        $pathResolver->expects($this->once())
            ->method('getFilePath')
            ->with('error500')
            ->willReturn('/path/to/error500.php');

        $logger->expects($this->once())->method('error')->with($exception);
        $logger->expects($this->never())->method('critical');

        $responseRenderer->expects($this->once())
            ->method('render')
            ->with($this->callback(function (HttpResponse $resp) {
                return $resp->viewPath === '/path/to/error500.php' && $resp->statusCode === 500;
            }));

        $handler = new ExceptionHandler($logger, $pathResolver, $responseRenderer);
        $handler->handleException($exception);
    }

    #[Test]
    public function it_handles_HttpResponse_logs_when_should_log_and_renders_status_based_view(): void
    {
        $logger = $this->createMock(Logger::class);
        $pathResolver = $this->createMock(PathResolver::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);

        $pathResolver->expects($this->once())
            ->method('isFileRegistered')
            ->with('error404')
            ->willReturn(true);

        $pathResolver->expects($this->once())
            ->method('getFilePath')
            ->with('error404')
            ->willReturn('/path/to/error404.php');

        $responseRenderer->expects($this->once())
            ->method('render')
            ->with($this->callback(function (HttpResponse $resp) {
                return $resp->viewPath === '/path/to/error404.php' && $resp->statusCode === 404;
            }));

        // create an Exception implementation of HttpExceptionInterface (so it's a Throwable)
        $anon = new class('msg') extends Exception implements HttpExceptionInterface {
            private int $codeStatus;
            private bool $shouldLog;

            public function __construct(string $m = 'msg', int $status = 404, bool $log = true)
            {
                parent::__construct($m);
                $this->codeStatus = $status;
                $this->shouldLog = $log;
            }

            public function getStatusCode(): int
            {
                return $this->codeStatus;
            }

            public function shouldLogException(): bool
            {
                return $this->shouldLog;
            }
        };

        // logger should receive the same anonymous exception instance
        $logger->expects($this->once())->method('error')->with($this->identicalTo($anon));

        $handler = new ExceptionHandler($logger, $pathResolver, $responseRenderer);
        $handler->handleException($anon);
    }

    #[Test]
    public function it_handles_HttpResponse_not_logged_when_should_log_is_false(): void
    {
        $logger = $this->createMock(Logger::class);
        $pathResolver = $this->createMock(PathResolver::class);
        $responseRenderer = $this->createMock(ResponseRenderer::class);

        $exception = new PageNotFoundException();

        $pathResolver->expects($this->once())
            ->method('isFileRegistered')
            ->with('error404')
            ->willReturn(true);

        $pathResolver->expects($this->once())
            ->method('getFilePath')
            ->with('error404')
            ->willReturn('/path/to/error404.php');

        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('critical');

        $responseRenderer->expects($this->once())
            ->method('render')
            ->with($this->callback(function (HttpResponse $resp) {
                return $resp->viewPath === '/path/to/error404.php' && $resp->statusCode === 404;
            }));

        $handler = new ExceptionHandler($logger, $pathResolver, $responseRenderer);
        $handler->handleException($exception);
    }

    #[Test]
    public function it_creates_ErrorException_returns_false_when_reporting_disabled_and_throws_when_enabled(): void
    {
        $logger = $this->createStub(Logger::class);
        $pathResolver = $this->createStub(PathResolver::class);
        $responseRenderer = $this->createStub(ResponseRenderer::class);

        $handler = new ExceptionHandler($logger, $pathResolver, $responseRenderer);

        // disable reporting for E_USER_NOTICE
        error_reporting(0);
        $result = $handler->createErrorException(E_USER_NOTICE, 'msg', __FILE__, __LINE__);
        $this->assertFalse($result);

        // enable reporting for E_USER_NOTICE and expect an ErrorException to be thrown
        error_reporting(E_ALL);
        $this->expectException(ErrorException::class);
        $handler->createErrorException(E_USER_NOTICE, 'msg', __FILE__, __LINE__);
    }

}