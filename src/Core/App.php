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
use Velo\Http\Request;
use Velo\Http\Responses\Response;
use Velo\Http\RequestMethod;
use Velo\Http\ResponseRenderer;
use Velo\Router\Middlewares\AddMiddlewaresTrait;
use Velo\Router\Pipeline\Exceptions\MiddlewareNotFoundException;
use Velo\Router\Pipeline\Exceptions\MustImplementMiddlewareInterfaceException;
use Velo\Router\Pipeline\Pipeline;
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
     * Runs the application with the given Request.
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
    public function run(Request $request): void
    {
        /**
         * @var Pipeline $pipeline
         */
        $pipeline = $this->container->get(Pipeline::class);

        $response = $this->executeMiddlewaresChainAndResolveRequest($request, $pipeline);

        $this->renderResponse($response, $request->method);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws MustImplementMiddlewareInterfaceException
     * @throws MiddlewareNotFoundException
     */
    private function executeMiddlewaresChainAndResolveRequest(Request $request, Pipeline $pipeline): Response
    {
        return $pipeline->executeMiddlewaresChain(
            $request,
            $this->middlewares,
            fn() => $this->router->resolve($request)
        );
    }

    /**
     * Renders the given Response with ResponseRenderer's render method.
     */
    private function renderResponse(Response $response, RequestMethod $requestMethod): void
    {
        /**
         * @var ResponseRenderer $responseRenderer
         */
        $responseRenderer = $this->container->get(ResponseRenderer::class);

        $responseRenderer->render($response, $requestMethod);
    }
}