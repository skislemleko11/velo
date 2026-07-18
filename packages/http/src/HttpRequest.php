<?php
declare(strict_types=1);

namespace Velo\Http;

class HttpRequest
{
    public string $method;

    public function __construct(
        public string $url,
        string        $method
    )
    {
        $this->method = strtoupper($method);
    }

    public function getPostArg(string $key, $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function getPostData(): array
    {
        return $_POST;
    }
}