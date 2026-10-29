<?php
declare(strict_types=1);

namespace Velo\Middlewares\AuthMiddleware;

use Closure;

final readonly class ApiAuthMiddlewareConfig
{
    /**
     * @param array<string, mixed> $responseForUnauthenticatedUser
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and array response.
     */
    public function __construct(
        public string   $sessionKeyName = 'user_id',
        public array    $responseForUnauthenticatedUser = ['error' => 'Unauthenticated'],
        public ?Closure $customResponseHandler = null
    )
    {
    }
}