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
        array       $unauthenticatedResponse = ['error' => 'Unauthenticated']
    ): HttpResponse
    {
        if (!isset($_SESSION['user_id']))
            return $this->getUnauthenticatedResponse($request, $unauthenticatedResponse);

        return $next($request);
    }

    private function getUnauthenticatedResponse(HttpRequest $request, array $unauthenticatedResponse): HttpResponse
    {
        if ($this->customResponseHandler)
            return ($this->customResponseHandler)($request, $unauthenticatedResponse);

        return new HttpResponse(
            viewPath: null,
            statusCode: 401,
            data: $unauthenticatedResponse
        );
    }
}