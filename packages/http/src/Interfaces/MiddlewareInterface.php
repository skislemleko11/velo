<?php
declare(strict_types=1);

namespace Velo\Http\Interfaces;

use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;

interface MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next): HttpResponse;
}