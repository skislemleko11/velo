<?php
declare(strict_types=1);

namespace Velo\Tests\Core;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Container\Container;
use Velo\Core\App;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;
use Velo\Http\ResponseRenderer;
use Velo\Router\Pipeline\Pipeline;
use Velo\Router\Router\Router;

#[AllowMockObjectsWithoutExpectations]
class AppTest extends TestCase
{
    protected Router $router;
    protected Container $container;
    protected Pipeline $pipeline;
    protected ResponseRenderer $responseRenderer;

    protected function setUp(): void
    {
        $this->router = $this->createMock(Router::class);
        $this->container = $this->createMock(Container::class);
        $this->responseRenderer = $this->createMock(ResponseRenderer::class);

        $this->pipeline = new Pipeline($this->container);

        $this->container
            ->method('get')
            ->willReturnCallback(function (string $class) {
                return match ($class) {
                    Pipeline::class => $this->pipeline,
                    ResponseRenderer::class => $this->responseRenderer,
                    default => null,
                };
            });
    }

    #[Test]
    public function it_calls_router_resolve_method(): void
    {
        $request = new HttpRequest('/', 'GET');
        $httpResponse = new HttpResponse();

        $app = new App($this->router, $this->container);

        $this->router->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($request))
            ->willReturn($httpResponse);

        $this->responseRenderer->expects($this->once())
            ->method('render')
            ->with($httpResponse);

        $app->run($request);
    }

    #[Test]
    public function it_calls_ResponseRenderer_render_method(): void
    {
        $request = new HttpRequest('/', 'GET');
        $httpResponse = new HttpResponse();

        $app = new App($this->router, $this->container);

        $this->router->expects($this->once())
            ->method('resolve')
            ->with($request)
            ->willReturn($httpResponse);

        $this->responseRenderer->expects($this->once())
            ->method('render')
            ->with($httpResponse);

        $app->run($request);
    }

    #[Test]
    public function it_executes_global_middlewares_before_resolving_route(): void
    {
        $request = new HttpRequest('/', 'GET');
        $expectedResponse = new HttpResponse(data: ['middleware' => 'executed']);

        $middleware = $this->createMock(MiddlewareInterface::class);

        $middleware->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (HttpRequest $req, callable $next) use ($expectedResponse) {
                return $expectedResponse;
            });

        $this->router->expects($this->never())
            ->method('resolve');

        $this->responseRenderer->expects($this->once())
            ->method('render')
            ->with($expectedResponse);

        $app = new App($this->router, $this->container);
        $app->addGlobalMiddleware($middleware);

        $app->run($request);
    }
}