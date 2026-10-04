<?php
declare(strict_types=1);

namespace Velo\Middlewares\AntiCsrf;

use Closure;

final readonly class AntiCsrfConfig
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 1 argument - Request request.
     */
    public function __construct(
        public string   $tokenName = 'csrf_token',
        public ?Closure $customResponseHandler = null
    )
    {
    }
}