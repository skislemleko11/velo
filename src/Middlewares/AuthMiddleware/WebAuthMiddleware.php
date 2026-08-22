<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\RedirectResponse;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\Http\RedirectUrl;

/**
 * Auth Middleware for Web. It's the opposite of WebGuestMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 * User ID is stored in $_SESSION['user_id'].
 */
readonly class WebAuthMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and string redirectUrl.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null
    )
    {
    }

    /**
     * Handles the given Request - if the user is authenticated (with 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForUnauthenticatedUser(request, redirectUnauthenticatedUserTo).
     */
    public function handle(
        Request  $request,
        callable $next,
        string   $redirectUnauthenticatedUserTo = '/login'
    ): Response
    {
        if (!$this->session->has('user_id')) {
            return $this->getResponseForUnauthenticatedUser($request, $redirectUnauthenticatedUserTo);
        }

        return $next($request);
    }

    /**
     * Returns the RedirectResponse for an unauthenticated user.
     *
     * Adds redirect param to redirectUrl, in order to redirect to this page when the user logs in.
     * Returns customResponseHandler(request, redirectUrl) if provided in constructor,
     * otherwise returns new RedirectResponse(redirectUrl).
     */
    private function getResponseForUnauthenticatedUser(Request $request, string $redirectUrl): Response
    {
        $redirectUrl = RedirectUrl::withRedirectParam($redirectUrl, $request->url);

        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $redirectUrl);
        }

        return new RedirectResponse($redirectUrl);
    }
}