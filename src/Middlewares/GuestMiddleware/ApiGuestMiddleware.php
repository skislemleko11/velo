<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;

readonly class ApiGuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?Closure $customResponseHandler = null,
    )
    {
    }

    public function handle(
        HttpRequest $request,
        callable    $next,
        array $responseForAuthenticatedUser = [
            'error' => 'This is reserved for unauthenticated users.'
        ]
    ): HttpResponse
    {
        if (isset($_SESSION['user_id']))
            return $this->getResponseForAuthenticatedUser($request, $responseForAuthenticatedUser);

        return $next($request);
    }

    private function getResponseForAuthenticatedUser(HttpRequest $request, array $response): HttpResponse
    {
        if ($this->customResponseHandler)
            return ($this->customResponseHandler)($request, $response);

        return new HttpResponse(
            statusCode: 403,
            data: $response
        );
    }
}