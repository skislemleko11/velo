<?php
declare(strict_types=1);

namespace Velo\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use Velo\Http\Request;
use Velo\Http\Responses\Response;
use Velo\Http\RequestMethod;
use Velo\Http\ResponseRenderer;
use Velo\Router\Middlewares\AddMiddlewaresTrait;
use Velo\Router\Pipeline\Exceptions\MustImplementMiddlewareInterfaceException;
use Velo\Router\Pipeline\Pipeline;
use Velo\Router\Router\Exceptions\InvalidControllerSignatureException;
use Velo\Router\Router\Exceptions\MethodNotAllowedException;
use Velo\Router\Router\Exceptions\NotFoundControllerException;
use Velo\Router\Router\Exceptions\NotFoundControllerMethodException;
use Velo\Router\Router\Exceptions\RouteNotFound;
use Velo\Router\Router\Router;

/**
 * Runs the application.
 */
final class App
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
     * @throws ContainerExceptionInterface
     * @throws MustImplementMiddlewareInterfaceException
     * @throws ReflectionException
     * @throws NotFoundControllerMethodException
     * @throws NotFoundControllerException
     * @throws InvalidControllerSignatureException
     * @throws RouteNotFound
     * @throws MethodNotAllowedException
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
     * @throws ReflectionException
     * @throws NotFoundControllerMethodException
     * @throws NotFoundControllerException
     * @throws InvalidControllerSignatureException
     * @throws RouteNotFound
     * @throws MethodNotAllowedException
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
     * @throws ContainerExceptionInterface
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