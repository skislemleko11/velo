<?php
declare(strict_types=1);

namespace Velo\Tests\Core;

use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Velo\Core\ThrowableHandler;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;
use Velo\Router\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Router\Exceptions\PageNotFoundException;

#[AllowMockObjectsWithoutExpectations]
class ThrowableHandlerTest extends TestCase
{
    protected int $originalErrorReporting;

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
        $logger = $this->createMock(LoggerInterface::class);
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

        $handler = new ThrowableHandler($logger, $pathResolver, $responseRenderer);
        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_handles_HttpResponse_logs_when_should_log_and_renders_status_based_view(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
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

        $logger->expects($this->once())->method('error')->with($this->identicalTo($anon));

        $handler = new ThrowableHandler($logger, $pathResolver, $responseRenderer);
        $handler->handleThrowable($anon);
    }

    #[Test]
    public function it_handles_HttpResponse_not_logged_when_should_log_is_false(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
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

        $handler = new ThrowableHandler($logger, $pathResolver, $responseRenderer);
        $handler->handleThrowable($exception);
    }

    #[Test]
    public function it_creates_ErrorException_returns_false_when_reporting_disabled_and_throws_when_enabled(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $pathResolver = $this->createStub(PathResolver::class);
        $responseRenderer = $this->createStub(ResponseRenderer::class);

        $handler = new ThrowableHandler($logger, $pathResolver, $responseRenderer);

        error_reporting(0);
        $result = $handler->throwErrorException(E_USER_NOTICE, 'msg', __FILE__, __LINE__);
        $this->assertFalse($result);

        error_reporting(E_ALL);
        $this->expectException(ErrorException::class);
        $handler->throwErrorException(E_USER_NOTICE, 'msg', __FILE__, __LINE__);
    }
}