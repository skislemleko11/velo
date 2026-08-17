<?php
declare(strict_types=1);

namespace Velo\Http;

use ValueError;

/**
 * Represents an HTTP request.
 */
class HttpRequest
{
    /**
     * The key used in forms to provide not supported by default request methods.
     */
    public const string METHOD_FORM_KEY = 'request_method';

    /**
     * @var string $urlPath Clean URL (no GET args)
     */
    public readonly string $urlPath;
    private(set) array $getParams = [];
    private(set) RequestMethod $method;

    public function __construct(
        public readonly string $url,
        RequestMethod          $method
    )
    {
        $this->urlPath = parse_url($url, PHP_URL_PATH) ?: '/';

        if ($queryString = parse_url($url, PHP_URL_QUERY)) {
            parse_str($queryString, $this->getParams);
        }

        if ($method === RequestMethod::POST && $formMethod = $this->getPostArg(self::METHOD_FORM_KEY)) {
            $this->method = RequestMethod::fromString($formMethod, $method);
        } else {
            $this->method = $method;
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

    public function changeMethodFromHeadToGet(): self
    {
        if ($this->method !== RequestMethod::HEAD) {
            throw new ValueError(
                "Cannot change HTTP request method: {$this->method->value} from get, because it is not HEAD."
            );
        }

        $this->method = RequestMethod::GET;

        return $this;
    }

    /**
     * Creates an instance of HttpRequest from global variables.
     */
    public static function fromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_URI'],
            RequestMethod::fromString($_SERVER['REQUEST_METHOD'])
        );
    }
}