<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Velo\Http\Request;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\SessionInterface;

/**
 * Auth Middleware for API. It's the opposite of ApiGuestMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 */
final readonly class ApiAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionInterface $session
    )
    {
    }

    /**
     * Handles the given Request - if the user is authenticated, returns the result of next(request),
     * otherwise, calls getResponseForUnauthenticatedUser(request, config).
     */
    public function handle(
        Request  $request,
        callable $next,
        ApiAuthConfig $config = new ApiAuthConfig()
    ): Response
    {
        if (!$this->session->has($config->sessionKeyName)) {
            return $this->getResponseForUnauthenticatedUser($request, $config);
        }

        return $next($request);
    }

    /**
     * Returns the JsonResponse for an unauthenticated user.
     *
     * Returns customResponseHandler(request, responseForUnauthenticatedUser) if provided in constructor,
     * otherwise returns the JsonResponse with statusCode: 401 and data: responseForUnauthenticatedUser.
     */
    private function getResponseForUnauthenticatedUser(Request $request, ApiAuthConfig $config): Response
    {
        if ($config->customResponseHandler) {
            return ($config->customResponseHandler)($request, $config->responseForUnauthenticatedUser);
        }

        return new JsonResponse(
            body: $config->responseForUnauthenticatedUser,
            statusCode: 401
        );
    }
}