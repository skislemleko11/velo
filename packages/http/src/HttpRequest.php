<?php
declare(strict_types=1);

namespace Velo\Http;

readonly class HttpRequest
{
    public string $url;
    public string $method;

    public function __construct(
        string $url,
        string $method
    )
    {
        $this->url = parse_url($url, PHP_URL_PATH);
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