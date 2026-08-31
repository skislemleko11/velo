<?php
declare(strict_types=1);

namespace Velo\Http;

final class HeadersUtils
{
    public static function makeLowerCaseAndTrim(string $headerValue): string
    {
        return strtolower(trim($headerValue));
    }

    public static function getHeadersFromServerSuperGlobal(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            $key = (string)$key;

            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $header = str_replace('_', '-', self::makeLowerCaseAndTrim(substr($key, 5)));

            $headers[$header] = (string)$value;
        }

        return $headers;
    }
}