<?php
declare(strict_types=1);

namespace Velo\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Velo\Container\Container;
use Velo\Container\Exceptions\InvalidParameterExceptions\InvalidParameterException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterIntersectionTypeHintException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterMissingTypeHintException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterNoDefaultValueException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterUnionTypeHintException;
use Velo\Container\Exceptions\IsNotInstantiableException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;
use Velo\Http\ResponseRenderer;
use Velo\Router\Exceptions\PageNotFoundException;
use Velo\Router\Pipeline\Exceptions\ControllerMethodInvalidReturnTypeException;
use Velo\Router\Pipeline\Exceptions\MiddlewareNotFoundException;
use Velo\Router\Pipeline\Exceptions\MustImplementMiddlewareInterfaceException;
use Velo\Router\Pipeline\Pipeline;
use Velo\Router\Router\Exceptions\NotFoundControllerException;
use Velo\Router\Router\Exceptions\NotFoundMethodException;
use Velo\Router\Router\Router;

class App
{
    private array $globalMiddlewares = [];

    public function __construct(
        private readonly Router             $router,
        private readonly ContainerInterface $container
    )
    {
    }

    public function addGlobalMiddleware(MiddlewareInterface|string|array $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @param HttpRequest $request
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidParameterException
     * @throws IsNotInstantiableException
     * @throws MiddlewareNotFoundException
     * @throws MustImplementMiddlewareInterfaceException
     * @throws NotFoundExceptionInterface
     * @throws ParameterIntersectionTypeHintException
     * @throws ParameterMissingTypeHintException
     * @throws ParameterNoDefaultValueException
     * @throws ParameterUnionTypeHintException
     * @throws ReflectionException
     */
    public function run(HttpRequest $request): void
    {
        /**
         * @var Pipeline $pipeline
         */
        $pipeline = $this->container->get(Pipeline::class);

        $response = $pipeline->executeMiddlewaresChain(
            $request,
            $this->globalMiddlewares,
            fn() => $this->resolve($request)
        );

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
    private function resolve(HttpRequest $request): HttpResponse
    {
        return $this->router->resolve($request);
    }

    /**
     * @param HttpResponse $response
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws InvalidParameterException
     * @throws ParameterIntersectionTypeHintException
     * @throws ParameterMissingTypeHintException
     * @throws ParameterNoDefaultValueException
     * @throws ParameterUnionTypeHintException
     * @throws IsNotInstantiableException
     */
    private function renderResponse(HttpResponse $response): void
    {
        $this->container->get(ResponseRenderer::class)
            ->render($response);
    }
}