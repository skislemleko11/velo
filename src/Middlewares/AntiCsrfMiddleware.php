<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Closure;
use Random\RandomException;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Middlewares\Exceptions\InvalidRequestMethodMiddlewareException;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Router\PathResolver\Exceptions\PathNotFoundException;
use Velo\Router\PathResolver\PathResolver;

/**
 * Protects against CSRF attacks.
 *
 * Anti CSRF protection is based on sessions and tokens.
 * Anti CSRF token is stored in $_SESSION['csrf_token'].
 * The token in the POST form should be called 'csrf_token'.
 */
readonly class AntiCsrfMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customResponseHandler Closure should take 1 argument - HttpRequest request.
     */
    public function __construct(
        private PathResolver $pathResolver,
        private ?Closure     $customResponseHandler = null,
    )
    {
    }

    /**
     * Handles the given HttpRequest.
     *
     * You cannot use it with GET method, it will result in InvalidRequestMethodMiddlewareException.
     * If the CSRF token is invalid, it will be regenerated and the result of getInvalidTokenResponse(request) will be returned.
     * If the CSRF token is valid, the request will be passed to the next middleware.
     *
     * @throws PathNotFoundException
     * @throws RandomException
     * @throws InvalidRequestMethodMiddlewareException
     */
    public function handle(HttpRequest $request, callable $next): HttpResponse
    {
        if ($request->method === 'GET') {
            throw new InvalidRequestMethodMiddlewareException(
                'Cannot use ' . self::class . ' with GET method!',
            );
        }

        $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
        $requestToken = (string)$request->getPostArg('csrf_token', '');

        if (!$sessionToken || !$requestToken || !hash_equals($sessionToken, $requestToken)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return $this->getInvalidTokenResponse($request);
        }

        return $next($request);
    }

    /**
     * Returns the HttpResponse for an invalid CSRF token.
     * Uses custom handler if provided,
     * otherwise returns the HttpResponse with viewPath: error403, statusCode: 403 and data: ['error' => 'Invalid anti CSRF token!']
     *
     * @throws PathNotFoundException
     */
    private function getInvalidTokenResponse(HttpRequest $request): HttpResponse
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request);
        }

        return new HttpResponse(
            $this->pathResolver->getFilePath('error403'),
            403,
            ['error' => 'Invalid anti CSRF token!']
        );
    }
}