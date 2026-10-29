<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Velo\Http\Request;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\SessionInterface;

/**
 * Guest Middleware for API. It's the opposite of ApiAuthMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 */
final readonly class ApiGuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionInterface $session
    )
    {
    }

    /**
     * Handles the given Request - if the user is unauthenticated, returns the result of next(request),
     * otherwise, calls getResponseForAuthenticatedUser(request, responseForAuthenticatedUser).
     */
    public function handle(
        Request  $request,
        callable $next,
        ApiGuestConfig $config = new ApiGuestConfig()
    ): Response
    {
        if ($this->session->has($config->sessionKeyName)) {
            return $this->getResponseForAuthenticatedUser($request, $config);
        }

        return $next($request);
    }

    /**
     * Returns the JsonResponse for an authenticated user.
     *
     * Returns customResponseHandler(request, response) if provided in constructor,
     * otherwise returns the JsonResponse with statusCode: 403 and data: response.
     */
    private function getResponseForAuthenticatedUser(Request $request, ApiGuestConfig $config): Response
    {
        if ($config->customResponseHandler) {
            return ($config->customResponseHandler)($request, $config->responseForAuthenticatedUser);
        }

        return new JsonResponse(
            body: $config->responseForAuthenticatedUser,
            statusCode: 403
        );
    }
}