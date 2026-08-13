<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\Interfaces\SessionInterface;

/**
 * Guest Middleware for API. It's the opposite of ApiAuthMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 * User ID is stored in $_SESSION['user_id'].
 */
readonly class ApiGuestMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - HttpRequest request and array response.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null,
    )
    {
    }

    /**
     * Handles the given HttpRequest - if the user is unauthenticated (no 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForAuthenticatedUser(request, responseForAuthenticatedUser).
     */
    public function handle(
        HttpRequest $request,
        callable    $next,
        array       $responseForAuthenticatedUser = [
            'error' => 'This is reserved for unauthenticated users.'
        ]
    ): HttpResponse
    {
        if ($this->session->has('user_id')) {
            return $this->getResponseForAuthenticatedUser($request, $responseForAuthenticatedUser);
        }

        return $next($request);
    }

    /**
     * Returns the HttpResponse for an authenticated user.
     *
     * Returns customResponseHandler(request, response) if provided in constructor,
     * otherwise returns the HttpResponse with statusCode: 403 and data: response.
     */
    private function getResponseForAuthenticatedUser(HttpRequest $request, array $response): HttpResponse
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $response);
        }

        return new HttpResponse(
            statusCode: 403,
            data: $response
        );
    }
}