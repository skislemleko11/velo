<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Velo\Http\RedirectUrl;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\RedirectResponse;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\SessionInterface;

/**
 * Auth Middleware for Web. It's the opposite of WebGuestMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 */
final readonly class WebAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionInterface $session
    )
    {
    }

    /**
     * Handles the given Request - if the user is authenticated, returns the result of next(request),
     * otherwise, calls getResponseForUnauthenticatedUser(request, redirectUnauthenticatedUserTo).
     */
    public function handle(
        Request  $request,
        callable $next,
        WebAuthConfig $config = new WebAuthConfig()
    ): Response
    {
        if (!$this->session->has($config->sessionKeyName)) {
            return $this->getResponseForUnauthenticatedUser($request, $config);
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
    private function getResponseForUnauthenticatedUser(Request $request, WebAuthConfig $config): Response
    {
        $redirectUrl = RedirectUrl::withRedirectParam($config->redirectUnauthenticatedUserTo, $request->url);

        if ($config->customResponseHandler) {
            return ($config->customResponseHandler)($request, $redirectUrl);
        }

        return new RedirectResponse($redirectUrl);
    }
}