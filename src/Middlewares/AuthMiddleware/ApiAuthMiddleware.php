<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;

readonly class ApiAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?Closure $customResponseHandler = null,
    )
    {
    }

    public function handle(
        HttpRequest $request,
        callable    $next,
        array       $responseForUnauthenticatedUser = ['error' => 'Unauthenticated']
    ): HttpResponse
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->getUnauthenticatedResponse($request, $responseForUnauthenticatedUser);
        }

        return $next($request);
    }

    private function getUnauthenticatedResponse(HttpRequest $request, array $response): HttpResponse
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request, $response);
        }

        return new HttpResponse(
            statusCode: 401,
            data: $response
        );
    }
}