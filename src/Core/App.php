<?php
declare(strict_types=1);

namespace Velo\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Velo\Container\Exceptions\InvalidParameterExceptions\UnexpectedInvalidParameterException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterIntersectionTypeException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterMissingTypeDeclarationException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterNoDefaultValueException;
use Velo\Container\Exceptions\InvalidParameterExceptions\ParameterUnionTypeException;
use Velo\Container\Exceptions\IsNotInstantiableException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;
use Velo\Router\Middlewares\AddMiddlewaresTrait;
use Velo\Router\Pipeline\Exceptions\ControllerMethodInvalidReturnTypeException;
use Velo\Router\Pipeline\Exceptions\MiddlewareNotFoundException;
use Velo\Router\Pipeline\Exceptions\MustImplementMiddlewareInterfaceException;
use Velo\Router\Pipeline\Pipeline;
use Velo\Router\Router\Exceptions\MethodNotAllowedException;
use Velo\Router\Router\Exceptions\MissingRequiredArgumentException;
use Velo\Router\Router\Exceptions\NotFoundControllerException;
use Velo\Router\Router\Exceptions\NotFoundControllerMethodException;
use Velo\Router\Router\Exceptions\RouteNotFound;
use Velo\Router\Router\Router;

/**
 * Runs the application.
 */
class App
{
    use AddMiddlewaresTrait {
        addMiddleware as addGlobalMiddleware;
        addMiddlewares as addGlobalMiddlewares;
    }

    public function __construct(
        private readonly Router             $router,
        private readonly ContainerInterface $container
    )
    {
    }

    /**
     * Runs the application with the given HttpRequest.
     *
     * @throws ContainerExceptionInterface
     * @throws UnexpectedInvalidParameterException
     * @throws IsNotInstantiableException
     * @throws MiddlewareNotFoundException
     * @throws MustImplementMiddlewareInterfaceException
     * @throws NotFoundExceptionInterface
     * @throws ParameterIntersectionTypeException
     * @throws ParameterMissingTypeDeclarationException
     * @throws ParameterNoDefaultValueException
     * @throws ParameterUnionTypeException
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
            $this->middlewares,
            fn() => $this->resolve($request)
        );

        $this->renderResponse($response);
    }

    /**
     * Resolves the given HttpRequest, it uses Router's resolve method.
     *
     * @param HttpRequest $request
     * @return HttpResponse
     * @throws ContainerExceptionInterface
     * @throws ControllerMethodInvalidReturnTypeException
     * @throws MiddlewareNotFoundException
     * @throws MustImplementMiddlewareInterfaceException
     * @throws NotFoundControllerException
     * @throws NotFoundExceptionInterface
     * @throws NotFoundControllerMethodException
     * @throws RouteNotFound
     * @throws ReflectionException
     * @throws \Velo\Router\Router\Exceptions\InvalidParameterExceptions\UnexpectedInvalidParameterException
     * @throws \Velo\Router\Router\Exceptions\InvalidParameterExceptions\ParameterMissingTypeDeclarationException
     * @throws \Velo\Router\Router\Exceptions\InvalidParameterExceptions\ParameterUnionTypeException
     * @throws MethodNotAllowedException
     * @throws MissingRequiredArgumentException
     * @throws \Velo\Router\Router\Exceptions\InvalidParameterExceptions\ParameterIntersectionTypeException
     */
    private function resolve(HttpRequest $request): HttpResponse
    {
        return $this->router->resolve($request);
    }

    /**
     * Renders the given HttpResponse with ResponseRenderer's render method.
     *
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws UnexpectedInvalidParameterException
     * @throws ParameterIntersectionTypeException
     * @throws ParameterMissingTypeDeclarationException
     * @throws ParameterNoDefaultValueException
     * @throws ParameterUnionTypeException
     * @throws IsNotInstantiableException
     * @throws ContainerExceptionInterface
     */
    private function renderResponse(HttpResponse $response): void
    {
        $this->container->get(ResponseRenderer::class)
            ->render($response);
    }
}