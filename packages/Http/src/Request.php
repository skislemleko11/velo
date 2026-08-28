<?php
declare(strict_types=1);

namespace Velo\Http;

use ValueError;

/**
 * Represents an HTTP request.
 */
class Request
{
    /**
     * The key used in forms to provide not supported by default request methods.
     */
    public const string METHOD_FORM_KEY = 'request_method';

    public readonly string $urlPath;
    private(set) array $getParams = [];
    private(set) RequestMethod $method;
    public readonly array $headers;

    public function __construct(
        public readonly string $url,
        RequestMethod          $method
    )
    {
        $this->urlPath = $this->getUrlPath($url);

        $this->setGetParamsIfExist($url);

        $this->method = $this->getMethod($method);

        $this->headers = $this->getHeadersFromServerSuperGlobal();
    }

    private function getUrlPath(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    private function setGetParamsIfExist(string $url): void
    {
        if ($queryString = parse_url($url, PHP_URL_QUERY)) {
            parse_str($queryString, $this->getParams);
        }
    }

    private function getMethod(RequestMethod $actualMethod): RequestMethod
    {
        if ($actualMethod === RequestMethod::POST && $formMethod = $this->getPostArg(self::METHOD_FORM_KEY)) {
            return RequestMethod::fromString($formMethod, $actualMethod);
        }

        return $actualMethod;
    }

    private function getHeadersFromServerSuperGlobal(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $header = str_replace(' ', '-',
                ucwords(
                    str_replace('_', ' ',
                        strtolower(
                            substr($key, 5)
                        )
                    )
                )
            );

            $headers[$header] = $value;
        }

        return $headers;
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
     * @throws ValueError
     */
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
     * Creates an instance of Request from global variables.
     */
    public static function fromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_URI'],
            RequestMethod::fromString($_SERVER['REQUEST_METHOD'])
        );
    }
}