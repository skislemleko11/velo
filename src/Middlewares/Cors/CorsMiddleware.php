<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

use Throwable;
use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Cors\Exceptions\CorsResponseActionException;
use Velo\Middlewares\Cors\Headers\CorsRequestHeaderName;
use Velo\Router\Middlewares\MiddlewareInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @throws CorsResponseActionException
     */
    public function handle(
        Request    $request,
        callable   $next,
        CorsConfig $config = new CorsConfig()
    ): Response
    {
        $requestedMethod = $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value);

        if (!$this->isPreflight($request, $requestedMethod)) {
            return $this->handleNonPreflightRequestOrWrapThrowable($request, $next, $config);
        }

        if ($this->isPreflightRequestAllowed($config, $request)) {
            return new CorsResponseProcessor($config, $this->getRequestOrigin($request))
                ->buildPreflightResponse();
        }

        return new NoContentResponse(403);
    }

    private function getRequestOrigin(Request $request): string|null
    {
        return $request->getHeader(CorsRequestHeaderName::ORIGIN->value);
    }

    private function isPreflight(Request $request, ?string $requestedMethod): bool
    {
        $origin = $this->getRequestOrigin($request);

        return $request->method === RequestMethod::OPTIONS && $origin !== null && $requestedMethod !== null;
    }

    /**
     * @throws CorsResponseActionException
     */
    private function handleNonPreflightRequestOrWrapThrowable(
        Request    $request,
        callable   $next,
        CorsConfig $config
    ): Response
    {
        $origin = $this->getRequestOrigin($request);

        try {
            $response = $next($request);

            if (
                $this->isOriginAllowed($origin, $config) &&
                $this->isMethodAllowed($request->method, $config->allowedMethods)
            ) {
                new CorsResponseProcessor($config, $origin)
                    ->addCorsHeaders($response);
            }

            return $response;
        } catch (Throwable $throwable) {
            throw new CorsResponseActionException(
                new CorsResponseProcessor($config, $origin),
                $throwable
            );
        }
    }

    private function isOriginAllowed(?string $origin, CorsConfig $config): bool
    {
        return $origin !== null &&
            ($config->allowAllOrigins || in_array($origin, $config->allowedOrigins, true));
    }

    /**
     * @param list<RequestMethod> $allowedMethods
     */
    private function isMethodAllowed(RequestMethod $method, array $allowedMethods): bool
    {
        return in_array($method, $allowedMethods, true);
    }

    private function isPreflightRequestAllowed(CorsConfig $config, Request $request): bool
    {
        $requestedMethod = RequestMethod::tryFromString(
            $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value, '')
        );

        if ($requestedMethod === RequestMethod::UNKNOWN) {
            return false;
        }

        $origin = $this->getRequestOrigin($request);

        if (!$this->isOriginAllowed($origin, $config) ||
            !$this->isMethodAllowed($requestedMethod, $config->allowedMethods)
        ) {
            return false;
        }

        $requestedHeaders = $this->parseHeadersList(
            $request->getHeader(CorsRequestHeaderName::REQUEST_HEADERS->value, '')
        );

        return $this->areHeadersAllowed($requestedHeaders, $config);
    }

    /**
     * @return list<string>
     */
    private function parseHeadersList(string $headers): array
    {
        if ($headers === '') {
            return [];
        }

        $headers = strtolower($headers);

        return array_map(
            'trim',
            explode(',', $headers)
        );
    }

    /**
     * @param list<string> $requestedHeaders
     */
    private function areHeadersAllowed(array $requestedHeaders, CorsConfig $config): bool
    {
        if ($config->allowAllHeaders) {
            return true;
        }

        foreach ($requestedHeaders as $header) {
            if (!in_array($header, $config->allowedHeaders, true)) {
                return false;
            }
        }

        return true;
    }
}