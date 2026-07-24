<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;

readonly class WebGuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?Closure $customResponseHandler = null,
    )
    {
    }

    public function handle(
        HttpRequest $request,
        callable    $next,
        string      $redirectAuthenticatedUserTo = '/'
    ): HttpResponse
    {
        if (isset($_SESSION['user_id'])) {
            return $this->getResponseForAuthenticatedUser($request, $redirectAuthenticatedUserTo);
        }

        return $next($request);
    }

    private function getResponseForAuthenticatedUser(HttpRequest $request, string $redirectUrl): HttpResponse
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $redirectUrl);
        }

        return HttpResponse::redirect($redirectUrl);
    }
}