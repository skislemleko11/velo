<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Middlewares\Cors\Headers\CorsRequestHeaderName;
use Velo\Router\Route;
use Velo\Router\Router\Interfaces\CorsRouterExtensionInterface;

final class CorsRouterExtension implements CorsRouterExtensionInterface
{
    public function isPreflight(Request $request): bool
    {
        return CorsRequestUtils::isPreflight($request);
    }

    public function getRequestedMethodFromPreflight(Request $request): RequestMethod
    {
        return RequestMethod::tryFromString(
            $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value, '')
        );
    }

    /**
     * Determines if the given Route has CorsMiddleware.
     *
     * IMPORTANT! CorsMiddleware can't be registered as a callable to be detected!
     */
    public function hasCorsMiddleware(Route $route): bool
    {
        $middlewares = $route->getMiddlewares();

        foreach ($middlewares as $middleware) {
            // No reason to use Reflection to handle a callable being CorsMiddleware factory function.
            // Because CorsMiddleware doesn't even need anything in the constructor
            if ($middleware instanceof CorsMiddleware ||
                $middleware === CorsMiddleware::class ||
                (is_array($middleware) && $middleware[0] === CorsMiddleware::class &&
                    (count($middleware) === 1 ||
                        (count($middleware) === 2 && is_array($middleware[1]))
                    )
                )
            ) {
                return true;
            }
        }

        return false;
    }
}