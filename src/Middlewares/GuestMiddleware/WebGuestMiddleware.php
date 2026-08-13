<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\Interfaces\SessionInterface;

/**
 * Guest Middleware for Web. It's the opposite of WebAuthMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 * User ID is stored in $_SESSION['user_id'].
 */
readonly class WebGuestMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - HttpRequest request and string redirectUrl.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null,
    )
    {
    }

    /**
     * Handles the given HttpRequest - if the user is unauthenticated (no 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForAuthenticatedUser(request, redirectAuthenticatedUserTo).
     */
    public function handle(
        HttpRequest $request,
        callable    $next,
        string      $redirectAuthenticatedUserTo = '/'
    ): HttpResponse
    {
        if ($this->session->has('user_id')) {
            return $this->getResponseForAuthenticatedUser($request, $redirectAuthenticatedUserTo);
        }

        return $next($request);
    }

    /**
     * Returns the HttpResponse for an authenticated user.
     *
     * Returns customResponseHandler(request, redirectUrl) if provided in constructor,
     * otherwise returns the HttpResponse::redirect(redirectUrl) result.
     */
    private function getResponseForAuthenticatedUser(HttpRequest $request, string $redirectUrl): HttpResponse
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $redirectUrl);
        }

        return HttpResponse::redirect($redirectUrl);
    }
}