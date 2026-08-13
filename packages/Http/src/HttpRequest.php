<?php
declare(strict_types=1);

namespace Velo\Http;

/**
 * Represents an HTTP request.
 */
class HttpRequest
{
    /**
     * @var string $urlPath Clean URL (no GET args)
     */
    public readonly string $urlPath;
    public readonly string $requestMethod;
    private(set) array $getParams = [];

    public function __construct(
        public readonly string $url,
        string                 $method
    )
    {
        $this->urlPath = parse_url($url, PHP_URL_PATH) ?: '/';
        $this->requestMethod = strtoupper($method);

        if ($queryString = parse_url($url, PHP_URL_QUERY)) {
            parse_str($queryString, $this->getParams);
        }
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