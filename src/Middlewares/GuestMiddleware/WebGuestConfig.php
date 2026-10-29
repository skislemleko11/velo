<?php
declare(strict_types=1);

namespace Velo\Middlewares\GuestMiddleware;

use Closure;

final readonly class WebGuestConfig
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 2 arguments - Request request and string redirectUrl.
     */
    public function __construct(
        public string   $sessionKeyName = 'user_id',
        public string   $redirectAuthenticatedUserTo = '/',
        public ?Closure $customResponseHandler = null
    )
    {
    }
}