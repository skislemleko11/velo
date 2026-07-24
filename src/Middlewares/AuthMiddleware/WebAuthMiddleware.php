<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;

readonly class WebAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?Closure $customResponseHandler = null,
    )
    {
    }

    public function handle(
        HttpRequest $request,
        callable    $next,
        string      $redirectUnauthenticatedUserTo = '/login'
    ): HttpResponse
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->getResponseForUnauthenticatedUser($request, $redirectUnauthenticatedUserTo);
        }

        return $next($request);
    }

    private function getResponseForUnauthenticatedUser(HttpRequest $request, string $redirectUrl): HttpResponse
    {
        $_SESSION['redirect_after_login'] = $request->url;

        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $redirectUrl);
        }

        return HttpResponse::redirect($redirectUrl);
    }
}