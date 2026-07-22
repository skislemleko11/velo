<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Closure;
use Random\RandomException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;
use Velo\Middlewares\Exceptions\InvalidRequestMethodMiddlewareException;
use Velo\Router\PathResolver\Exceptions\PathNotFoundException;
use Velo\Router\PathResolver\PathResolver;

readonly class AntiCsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PathResolver $pathResolver,
        private ?Closure     $customResponseHandler = null,
    )
    {
    }

    /**
     * @throws PathNotFoundException
     * @throws RandomException
     * @throws InvalidRequestMethodMiddlewareException
     */
    public function handle(HttpRequest $request, callable $next): HttpResponse
    {
        if ($request->method === 'GET')
            throw new InvalidRequestMethodMiddlewareException(
                'Cannot use ' . self::class . ' with GET method!',
            );

        $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
        $requestToken = (string)$request->getPostArg('csrf_token', '');

        if (!$sessionToken || !$requestToken || !hash_equals($sessionToken, $requestToken)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return $this->getInvalidTokenResponse($request);
        }

        return $next($request);
    }

    /**
     * @throws PathNotFoundException
     */
    private function getInvalidTokenResponse(HttpRequest $request): HttpResponse
    {
        if ($this->customResponseHandler)
            return ($this->customResponseHandler)($request);

        return new HttpResponse(
            $this->pathResolver->getFilePath('error403'),
            403,
            ['error' => 'Invalid anti CSRF token!']
        );
    }
}