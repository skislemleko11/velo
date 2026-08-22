<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares;

use PHPUnit\Framework\Attributes\Test;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\ViewResponse;
use Velo\Logger\Logger;
use Velo\Middlewares\RequestLoggerMiddleware;
use PHPUnit\Framework\TestCase;
use Velo\Http\RequestMethod;

final class RequestLoggerMiddlewareTest extends TestCase
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

        $middleware = new RequestLoggerMiddleware($this->logger, function (Request $request) use (&$wasCalled) {
            $wasCalled++;
            return $request;
        });

        $wasCalledNext = 0;
        $httpResponse = new ViewResponse('hehe');
        $next = function (Request $request) use (&$wasCalledNext, $httpResponse) {
            $wasCalledNext++;
            return $httpResponse;
        };

        $request = new Request('/', RequestMethod::GET);
        $this->assertSame($httpResponse, $middleware->handle($request, $next));

        $this->assertEquals(1, $wasCalled);
        $this->assertEquals(1, $wasCalledNext);
    }

    #[Test]
    public function it_uses_provided_logger_when_no_custom_log_function()
    {
        $middleware = new RequestLoggerMiddleware($this->logger);

        $wasCalledNext = 0;
        $httpResponse = new ViewResponse('hehe');
        $next = function (Request $request) use (&$wasCalledNext, $httpResponse) {
            $wasCalledNext++;
            return $httpResponse;
        };

        $request = new Request('/', RequestMethod::GET);

        $this->logger->expects($this->once())
            ->method('info')
            ->with("Request:\nUrl: {url}\nMethod: {method}", [
                'url' => $request->urlPath,
                'url path' => $request->urlPath,
                'method' => $request->method->value,
                'GET params' => $request->getParams
            ]);


        $this->assertSame($httpResponse, $middleware->handle($request, $next));
        $this->assertEquals(1, $wasCalledNext);
    }
}
