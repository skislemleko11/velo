<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\Cors;

use Velo\Http\Request;
use Velo\Http\RequestMethod;

final class CorsTestsUtils
{
    /**
     * @param array<string, string> $headers
     */
    public static function createRequest(
        RequestMethod $method,
        array         $headers = [],
    ): Request
    {
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                unset($_SERVER[$name]);
            }
        }

        $request = new Request('', $method);

        foreach ($headers as $name => $value) {
            $name = trim(strtoupper(str_replace('-', '_', $name)));
            $_SERVER['HTTP_' . $name] = $value;
        }

        return $request;
    }
}