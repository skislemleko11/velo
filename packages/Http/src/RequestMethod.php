<?php
declare(strict_types=1);

namespace Velo\Http;

enum RequestMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';

    public static function fromString(string $method): self
    {
        $method = strtoupper($method);

        return self::tryFrom($method) ?? self::GET;
    }
}
