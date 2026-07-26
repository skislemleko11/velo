<?php
declare(strict_types=1);

namespace Velo\Http;

/**
 * Represents an HTTP request.
 */
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

    /**
     * Gets POST key, returns default value if the key is not set.
     */
    public function getPostArg(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Returns $_POST superglobal.
     */
    public function getPostData(): array
    {
        return $_POST;
    }

    /**
     * Creates an instance of HttpRequest from global variables.
     */
    public static function fromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD']
        );
    }
}