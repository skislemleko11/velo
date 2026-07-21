<?php
declare(strict_types=1);

namespace Velo\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Velo\Container\Container;
use Velo\Container\Exceptions\ContainerException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;
use Velo\Router\Exceptions\PageNotFoundException;
use Velo\Router\Pipeline\Exceptions\ControllerMethodInvalidReturnTypeException;
use Velo\Router\Pipeline\Exceptions\MustImplementMiddlewareInterfaceException;
use Velo\Router\Router\Exceptions\NotFoundControllerException;
use Velo\Router\Router\Exceptions\NotFoundMethodException;
use Velo\Router\Router\Router;

readonly class App
{
    public function __construct(
        protected Router    $router,
        protected Container $container
    )
    {
    }

    /**
     * @throws MustImplementMiddlewareInterfaceException
     * @throws NotFoundControllerException
     * @throws NotFoundExceptionInterface
     * @throws NotFoundMethodException
     * @throws ControllerMethodInvalidReturnTypeException
     * @throws ContainerExceptionInterface
     * @throws PageNotFoundException
     * @throws ReflectionException
     */
    public function run(HttpRequest $request): void
    {
        $response = $this->resolve($request);
        $this->renderResponse($response);
    }

    /**
     * @throws NotFoundControllerException
     * @throws MustImplementMiddlewareInterfaceException
     * @throws NotFoundExceptionInterface
     * @throws NotFoundMethodException
     * @throws ControllerMethodInvalidReturnTypeException
     * @throws ContainerExceptionInterface
     * @throws PageNotFoundException
     * @throws ReflectionException
     */
    protected function resolve(HttpRequest $request): HttpResponse
    {
        return $this->router->resolve($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    protected function renderResponse(HttpResponse $response): void
    {
        $this->container->get(ResponseRenderer::class)->render($response);
    }
}