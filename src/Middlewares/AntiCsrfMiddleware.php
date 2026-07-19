<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Random\RandomException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;
use Velo\Router\Exceptions\PathNotFoundException;
use Velo\Router\PathResolver;
use Velo\Middlewares\Exceptions\CannotUseAntiCsrfMiddlewareWithGetMethodException;

readonly class AntiCsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private PathResolver $pathResolver)
    {
    }

    /**
     * @throws PathNotFoundException
     * @throws RandomException
     */
    public function handle(HttpRequest $request, callable $next): HttpResponse
    {
        if ($request->method === 'GET')
            throw new CannotUseAntiCsrfMiddlewareWithGetMethodException();

        if (!isset($_SESSION['csrf_token']))
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if ($request->getPostArg('csrf_token') !== $_SESSION['csrf_token'])
            return new HttpResponse($this->pathResolver->getFilePath('error500'), 500);

        return $next($request);
    }
}