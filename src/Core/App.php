<?php
declare(strict_types=1);

namespace Velo\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;
use ReflectionException;
use SensitiveParameter;
use Velo\Container\Container;
use Velo\Container\Exceptions\ContainerException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Router\Exceptions\ControllerMethodInvalidReturnTypeException;
use Velo\Router\Exceptions\MustImplementMiddlewareInterfaceException;
use Velo\Router\Exceptions\NotFoundControllerException;
use Velo\Router\Exceptions\NotFoundMethodException;
use Velo\Router\Exceptions\PageNotFoundException;
use Velo\Router\Router;
use Velo\Http\ResponseRenderer;

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
     * @throws RandomException
     * @throws NotFoundMethodException
     * @throws ControllerMethodInvalidReturnTypeException
     * @throws ContainerExceptionInterface
     * @throws PageNotFoundException
     * @throws ReflectionException
     */
    public function run(HttpRequest $request, #[SensitiveParameter] array &$session): void
    {
        $this->setCsrfToken($session);
        $response = $this->resolve($request);
        $this->renderResponse($response);
    }

    /**
     * @throws RandomException
     */
    protected function setCsrfToken(#[SensitiveParameter] array &$session): void
    {
        if (!isset($session['csrf_token']))
            $session['csrf_token'] = bin2hex(random_bytes(32));
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