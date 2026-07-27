<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Router\Middlewares\MiddlewareInterface;

/**
 * Auth Middleware for API. It's the opposite of ApiGuestMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 * User ID is stored in $_SESSION['user_id'].
 */
readonly class ApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - HttpRequest request and array response.
     */
    public function __construct(
        private ?Closure $customResponseHandler = null,
    )
    {
    }

    /**
     * Handles the given HttpRequest - if the user is authenticated (with 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForUnauthenticatedUser(request, responseForUnauthenticatedUser).
     */
    public function handle(
        HttpRequest $request,
        callable    $next,
        array       $responseForUnauthenticatedUser = ['error' => 'Unauthenticated']
    ): HttpResponse
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->getResponseForUnauthenticatedUser($request, $responseForUnauthenticatedUser);
        }

        return $next($request);
    }

    /**
     * Returns the HttpResponse for an unauthenticated user.
     *
     * Returns customResponseHandler(request, response) if provided in constructor,
     * otherwise returns the HttpResponse with statusCode: 401 and data: response.
     */
    private function getResponseForUnauthenticatedUser(HttpRequest $request, array $response): HttpResponse
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $response);
        }

        return new HttpResponse(
            statusCode: 401,
            data: $response
        );
    }
}