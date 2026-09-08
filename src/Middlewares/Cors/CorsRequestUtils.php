<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Middlewares\Cors\Headers\CorsRequestHeaderName;

final class CorsRequestUtils
{
    public static function isPreflight(Request $request): bool
    {
        $origin = self::getRequestOrigin($request);
        $requestedMethod = $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value);

        return $request->method === RequestMethod::OPTIONS && $origin !== null && $requestedMethod !== null;
    }

    public static function getRequestOrigin(Request $request): string|null
    {
        return $request->getHeader(CorsRequestHeaderName::ORIGIN->value);
    }

    public static function isPreflightRequestAllowed(CorsConfig $config, Request $request): bool
    {
        $requestedMethod = RequestMethod::tryFromString(
            $request->getHeader(CorsRequestHeaderName::REQUEST_METHOD->value, '')
        );

        if ($requestedMethod === RequestMethod::UNKNOWN) {
            return false;
        }

        $origin = self::getRequestOrigin($request);

        if (!self::isOriginAllowed($origin, $config) ||
            !self::isMethodAllowed($requestedMethod, $config->allowedMethods)
        ) {
            return false;
        }

        $requestedHeaders = self::parseHeadersList(
            $request->getHeader(CorsRequestHeaderName::REQUEST_HEADERS->value, '')
        );

        return self::areHeadersAllowed($requestedHeaders, $config);
    }

    public static function isOriginAllowed(?string $origin, CorsConfig $config): bool
    {
        return $origin !== null &&
            ($config->allowAllOrigins || in_array($origin, $config->allowedOrigins, true));
    }

    /**
     * @param list<RequestMethod> $allowedMethods
     */
    public static function isMethodAllowed(RequestMethod $method, array $allowedMethods): bool
    {
        return in_array($method, $allowedMethods, true);
    }

    /**
     * @return list<string>
     */
    private static function parseHeadersList(string $headers): array
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
    private static function areHeadersAllowed(array $requestedHeaders, CorsConfig $config): bool
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