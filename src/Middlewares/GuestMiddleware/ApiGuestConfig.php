<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;

final readonly class ApiGuestConfig
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and array response.
     */
    public function __construct(
        public string   $sessionKeyName = 'user_id',
        public array    $responseForAuthenticatedUser = [
            'error' => 'This is reserved for unauthenticated users.'
        ],
        public ?Closure $customResponseHandler = null
    )
    {
    }
}