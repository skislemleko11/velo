<?php
declare(strict_types=1);

namespace Velo\Http;

use ValueError;

enum RequestMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case QUERY = 'QUERY';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';

    public static function tryFromString(string $method, ?RequestMethod $default = self::GET): ?self
    {
        $method = strtoupper($method);

        return self::tryFrom($method) ?? $default;
    }
}
