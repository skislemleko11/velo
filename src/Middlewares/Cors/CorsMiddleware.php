<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

use Throwable;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Cors\Exceptions\CorsResponseActionException;
use Velo\Router\Middlewares\MiddlewareInterface;

/**
 * CORS middleware.
 *
 * IMPORTANT! Don't bind it to Routes with using a callable function, because it won't work for Preflight Requests!
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @throws CorsResponseActionException
     */
    public function handle(
        Request    $request,
        callable   $next,
        CorsConfig $config = new CorsConfig()
    ): Response
    {
        if (!CorsRequestUtils::isPreflight($request)) {
            return $this->handleNonPreflightRequestOrWrapThrowable($request, $next, $config);
        }

        if (CorsRequestUtils::isPreflightRequestAllowed($config, $request)) {
            return new CorsResponseProcessor($config, CorsRequestUtils::getRequestOrigin($request))
                ->buildPreflightResponse();
        }

        return new NoContentResponse(403);
    }

    /**
     * @throws CorsResponseActionException
     */
    private function handleNonPreflightRequestOrWrapThrowable(
        Request    $request,
        callable   $next,
        CorsConfig $config
    ): Response
    {
        $origin = CorsRequestUtils::getRequestOrigin($request);

        try {
            $response = $next($request);

            if (
                CorsRequestUtils::isOriginAllowed($origin, $config) &&
                CorsRequestUtils::isMethodAllowed($request->method, $config->allowedMethods)
            ) {
                new CorsResponseProcessor($config, $origin)
                    ->addCorsHeaders($response);
            }

            return $response;
        } catch (Throwable $throwable) {
            if ($origin === null) {
                throw $throwable;
            }

            throw new CorsResponseActionException(
                new CorsResponseProcessor($config, $origin),
                $throwable
            );
        }
    }
}