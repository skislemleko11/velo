<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Closure;
use Random\RandomException;
use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\Request;
use Velo\Http\Responses\Concrete\ViewResponse;
use Velo\Middlewares\Exceptions\InvalidRequestMethodMiddlewareExceptionInterface;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Response;
use Velo\Http\Responses\Concrete\JsonResponse;

/**
 * Protects against CSRF attacks.
 *
 * Anti CSRF protection is based on sessions and tokens.
 * Anti CSRF token is stored in $_SESSION['csrf_token'].
 * The token in the POST form should be called 'csrf_token'.
 */
readonly class AntiCsrfMiddleware implements MiddlewareInterface
{
    private const string CSRF_TOKEN_NAME = 'csrf_token';

    /**
     * @param Closure|null $customResponseHandler Closure should take 1 argument - Request request.
     */
    public function __construct(
        private PathResolver     $pathResolver,
        private SessionInterface $session,
        private ?Closure         $customResponseHandler = null
    )
    {
    }

    /**
     * Handles the given Request.
     *
     * You cannot use it with GET method, it will result in InvalidRequestMethodMiddlewareException.
     * If the CSRF token is invalid, it will be regenerated and the result of getInvalidTokenResponse(request) will be returned.
     * If the CSRF token is valid, the request will be passed to the next middleware.
     *
     * @throws PathNotFoundException
     * @throws RandomException
     * @throws InvalidRequestMethodMiddlewareExceptionInterface
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($request->method === RequestMethod::GET) {
            throw new InvalidRequestMethodMiddlewareExceptionInterface(
                'Cannot use ' . self::class . ' with GET method!',
            );
        }

        $sessionToken = (string)$this->session->get(self::CSRF_TOKEN_NAME);
        $requestToken = (string)$request->getPostArg(self::CSRF_TOKEN_NAME);

        if (!$sessionToken || !$requestToken || !hash_equals($sessionToken, $requestToken)) {
            $this->session->setCsrfToken(
                bin2hex(random_bytes(32))
            );

            return $this->getInvalidTokenResponse($request);
        }

        return $next($request);
    }

    /**
     * Returns the Response for an invalid CSRF token.
     *
     * Uses custom handler if provided,
     * otherwise returns the ViewResponse with viewPath: error403, statusCode: 403 and data: ['error' => 'Invalid anti CSRF token!']
     * or JsonResponse if the view file is not registered.
     *
     * @throws PathNotFoundException
     */
    private function getInvalidTokenResponse(Request $request): Response
    {
        if ($this->customResponseHandler) {
            return ($this->customResponseHandler)($request);
        }

        if ($viewPath = $this->pathResolver->getFilePath('error403')) {
            return new ViewResponse(
                $viewPath,
                ['error' => 'Invalid anti CSRF token!'],
                403
            );
        } else {
            return new JsonResponse(['error' => 'Invalid anti CSRF token!'], 403);
        }
    }
}