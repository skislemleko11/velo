<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\RedirectResponse;
use Velo\Http\Responses\Response;
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
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and string redirectUrl.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null,
    )
    {
    }

    /**
     * Handles the given Request - if the user is unauthenticated (no 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForAuthenticatedUser(request, redirectAuthenticatedUserTo).
     */
    public function handle(
        Request  $request,
        callable $next,
        string   $redirectAuthenticatedUserTo = '/'
    ): Response
    {
        if ($this->session->has('user_id')) {
            return $this->getResponseForAuthenticatedUser($request, $redirectAuthenticatedUserTo);
        }

        return $next($request);
    }

    /**
     * Returns the RedirectResponse for an authenticated user.
     *
     * Returns customResponseHandler(request, redirectUrl) if provided in constructor,
     * otherwise returns new RedirectResponse(redirectUrl).
     */
    private function getResponseForAuthenticatedUser(Request $request, string $redirectUrl): Response
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $redirectUrl);
        }

        return new RedirectResponse($redirectUrl);
    }
}