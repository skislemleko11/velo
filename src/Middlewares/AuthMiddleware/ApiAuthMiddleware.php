<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\Interfaces\SessionInterface;

/**
 * Auth Middleware for API. It's the opposite of ApiGuestMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 * User ID is stored in $_SESSION['user_id'].
 */
readonly class ApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and array response.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null
    )
    {
    }

    /**
     * Handles the given Request - if the user is authenticated (with 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForUnauthenticatedUser(request, responseForUnauthenticatedUser).
     *
     * @param array<string, mixed> $responseForUnauthenticatedUser
     */
    public function handle(
        Request  $request,
        callable $next,
        array    $responseForUnauthenticatedUser = ['error' => 'Unauthenticated']
    ): Response
    {
        if (!$this->session->has('user_id')) {
            return $this->getResponseForUnauthenticatedUser($request, $responseForUnauthenticatedUser);
        }

        return $next($request);
    }

    /**
     * Returns the JsonResponse for an unauthenticated user.
     *
     * Returns customResponseHandler(request, response) if provided in constructor,
     * otherwise returns the JsonResponse with statusCode: 401 and data: response.
     *
     * @param array<string, mixed> $response
     */
    private function getResponseForUnauthenticatedUser(Request $request, array $response): Response
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $response);
        }

        return new JsonResponse(
            body: $response,
            statusCode: 401
        );
    }
}