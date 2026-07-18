<?php
declare(strict_types=1);

namespace Velo\Tests;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Core\App;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;
use Velo\Router\Router;
use Velo\Container\Container;

#[AllowMockObjectsWithoutExpectations]
class AppTest extends TestCase
{
    protected Router $router;
    protected Container $container;

    protected function setUp(): void
    {
        $this->router = $this->createMock(Router::class);
        $this->container = $this->createMock(Container::class);
    }

    #[Test]
    public function it_sets_csrf_token(): void
    {
        $app = $this->getMockBuilder(App::class)
            ->setConstructorArgs([$this->router, $this->container])
            ->onlyMethods(['resolve', 'renderResponse'])
            ->getMock();
        $app->method('resolve')
            ->willReturn(new HttpResponse());

        $session = [];

        $request = new HttpRequest('/', 'GET');
        $app->run($request, $session);

        $this->assertTrue(isset($session['csrf_token']));
    }

    #[Test]
    public function it_calls_router_resolve_method(): void
    {
        $app = $this->getMockBuilder(App::class)
            ->setConstructorArgs([$this->router, $this->container])
            ->onlyMethods(['setCsrfToken', 'renderResponse'])
            ->getMock();

        $request = new HttpRequest('/', 'GET');

        $this->router->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($request))
            ->willReturn(new HttpResponse());

        $session = [];
        $app->run($request, $session);
    }

    #[Test]
    public function it_calls_ResponseRenderer_render_method(): void
    {
        $app = $this->getMockBuilder(App::class)
            ->setConstructorArgs([$this->router, $this->container])
            ->onlyMethods(['setCsrfToken', 'resolve'])
            ->getMock();

        $httpResponse = new HttpResponse();
        $app->method('resolve')
            ->willReturn($httpResponse);

        $responseRenderer = $this->createMock(ResponseRenderer::class);

        $responseRenderer->expects($this->once())
            ->method('render')
            ->with($httpResponse);

        $this->container->expects($this->once())
            ->method('get')
            ->with(ResponseRenderer::class)
            ->willReturn($responseRenderer);

        $session = [];
        $request = new HttpRequest('/', 'GET');
        $app->run($request, $session);
    }
}