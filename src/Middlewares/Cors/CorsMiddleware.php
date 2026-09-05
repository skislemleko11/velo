<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Cors\CorsConfig\CorsConfig;
use Velo\Router\Middlewares\MiddlewareInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(
        Request    $request,
        callable   $next,
        CorsConfig $config = new CorsConfig(),
    ): Response
    {
        $origin = $request->getHeader(CorsRequestHeaderName::ORIGIN->value);
        $requestedMethod = $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value);

        if (!$this->isPreflight($request, $origin, $requestedMethod)) {
            $response = $next($request);

            if (
                $this->isMethodAllowed($request->method, $config->allowedMethods) &&
                $this->isOriginAllowed($origin, $config)
            ) {
                new CorsResponseProcessor($config, $origin)
                    ->addCorsHeaders($response);
            }

            return $response;
        }


        if ($this->isPreflightRequestAllowed($config, $request)) {
            return new CorsResponseProcessor($config, $origin)
                ->buildPreflightResponse();
        }

        return new NoContentResponse(403);
    }

    private function isOriginAllowed(?string $origin, CorsConfig $config): bool
    {
        return $origin !== null &&
            ($config->allowAllOrigins || in_array($origin, $config->allowedOrigins, true));
    }

    private function isPreflight(Request $request, ?string $origin, ?string $requestedMethod): bool
    {
        return $request->method === RequestMethod::OPTIONS && $origin !== null && $requestedMethod !== null;
    }

    private function isPreflightRequestAllowed(CorsConfig $config, Request $request): bool
    {
        $requestedMethod = RequestMethod::tryFromString(
            $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value, ''),
            null);

        if ($requestedMethod === null) {
            return false;
        }

        if (!$this->isOriginAllowed($request->getHeader(CorsRequestHeaderName::ORIGIN->value), $config) ||
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
     * @param list<RequestMethod> $allowedMethods
     */
    private function isMethodAllowed(RequestMethod $method, array $allowedMethods): bool
    {
        return in_array($method, $allowedMethods, true);
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