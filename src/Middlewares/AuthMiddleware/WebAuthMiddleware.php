<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
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
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - HttpRequest request and string redirectUrl.
     */
    public function __construct(
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null
    )
    {
    }

    /**
     * Handles the given HttpRequest - if the user is authenticated (with 'user_id' in $_SESSION), returns the result of next(request),
     * otherwise, calls getResponseForUnauthenticatedUser(request, redirectUnauthenticatedUserTo).
     */
    public function handle(
        HttpRequest $request,
        callable    $next,
        string      $redirectUnauthenticatedUserTo = '/login'
    ): HttpResponse
    {
        if (!$this->session->has('user_id')) {
            return $this->getResponseForUnauthenticatedUser($request, $redirectUnauthenticatedUserTo);
        }

        return $next($request);
    }

    /**
     * Returns the HttpResponse for an unauthenticated user.
     *
     * Adds redirect param to redirectUrl, in order to redirect to this page when the user logs in.
     * Returns customResponseHandler(request, redirectUrl) if provided in constructor,
     * otherwise returns the HttpResponse::redirect(redirectUrl) result.
     */
    private function getResponseForUnauthenticatedUser(HttpRequest $request, string $redirectUrl): HttpResponse
    {
        $redirectUrl = RedirectUrl::withRedirectParam($redirectUrl, $request->url);

        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $redirectUrl);
        }

        return HttpResponse::redirect($redirectUrl);
    }
}