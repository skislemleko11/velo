<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Velo\Http\Request;
use Velo\Http\Responses\Concrete\RedirectResponse;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\SessionInterface;

/**
 * Guest Middleware for Web. It's the opposite of WebAuthMiddleware.
 *
 * Authentication is handled with session-based User IDs.
 */
final readonly class WebGuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionInterface $session
    )
    {
    }

    /**
     * Handles the given Request - if the user is unauthenticated, returns the result of next(request),
     * otherwise, calls getResponseForAuthenticatedUser(request, redirectAuthenticatedUserTo).
     */
    public function handle(
        Request        $request,
        callable       $next,
        WebGuestConfig $config = new WebGuestConfig()
    ): Response
    {
        if ($this->session->has($config->sessionKeyName)) {
            return $this->getResponseForAuthenticatedUser($request, $config);
        }

        return $next($request);
    }

    /**
     * Returns the RedirectResponse for an authenticated user.
     *
     * Returns customResponseHandler(request, redirectUrl) if provided in constructor,
     * otherwise returns new RedirectResponse(redirectUrl).
     */
    private function getResponseForAuthenticatedUser(Request $request, WebGuestConfig $config): Response
    {
        if ($config->customResponseHandler) {
            return ($config->customResponseHandler)($request, $config->redirectAuthenticatedUserTo);
        }

        return new RedirectResponse($config->redirectAuthenticatedUserTo);
    }
}