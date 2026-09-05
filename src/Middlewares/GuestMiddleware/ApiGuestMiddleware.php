<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Response;
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
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and array response.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null,
    )
    {
    }

    /**
     * Handles the given Request - if the user is unauthenticated (no 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForAuthenticatedUser(request, responseForAuthenticatedUser).
     *
     * @param array<string, mixed> $responseForAuthenticatedUser
     */
    public function handle(
        Request  $request,
        callable $next,
        array    $responseForAuthenticatedUser = [
            'error' => 'This is reserved for unauthenticated users.'
        ]
    ): Response
    {
        if ($this->session->has('user_id')) {
            return $this->getResponseForAuthenticatedUser($request, $responseForAuthenticatedUser);
        }

        return $next($request);
    }

    /**
     * Returns the JsonResponse for an authenticated user.
     *
     * Returns customResponseHandler(request, response) if provided in constructor,
     * otherwise returns the JsonResponse with statusCode: 403 and data: response.
     *
     * @param array<string, mixed> $response
     */
    private function getResponseForAuthenticatedUser(Request $request, array $response): Response
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $response);
        }

        return new JsonResponse(
            body: $response,
            statusCode: 403
        );
    }
}