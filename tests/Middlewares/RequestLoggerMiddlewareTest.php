<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares;

use PHPUnit\Framework\Attributes\Test;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Logger\Logger;
use Velo\Middlewares\RequestLoggerMiddleware;
use PHPUnit\Framework\TestCase;

class RequestLoggerMiddlewareTest extends TestCase
{
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
    }

    #[Test]
    public function it_uses_custom_log_function_when_provided()
    {
        $this->logger->expects($this->never())->method('info');

        $wasCalled = 0;

        $middleware = new RequestLoggerMiddleware($this->logger, function (HttpRequest $request) use (&$wasCalled) {
            $wasCalled++;
            return $request;
        });

        $wasCalledNext = 0;
        $httpResponse = new HttpResponse();
        $next = function (HttpRequest $request) use (&$wasCalledNext, $httpResponse) {
            $wasCalledNext++;
            return $httpResponse;
        };

        $request = new HttpRequest('/', 'get');
        $this->assertSame($httpResponse, $middleware->handle($request, $next));

        $this->assertEquals(1, $wasCalled);
        $this->assertEquals(1, $wasCalledNext);
    }

    #[Test]
    public function it_uses_provided_logger_when_no_custom_log_function()
    {
        $middleware = new RequestLoggerMiddleware($this->logger);

        $wasCalledNext = 0;
        $httpResponse = new HttpResponse();
        $next = function (HttpRequest $request) use (&$wasCalledNext, $httpResponse) {
            $wasCalledNext++;
            return $httpResponse;
        };

        $request = new HttpRequest('/', 'get');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Request', [
                'url' => $request->url,
                'method' => $request->method,
            ]);


        $this->assertSame($httpResponse, $middleware->handle($request, $next));
        $this->assertEquals(1, $wasCalledNext);
    }
}
