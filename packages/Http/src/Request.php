<?php
declare(strict_types=1);

namespace Velo\Http;

use ValueError;

/**
 * Represents an HTTP request.
 */
final class Request
{
    /**
     * The key used in forms to provide not supported by default request methods.
     */
    public const string METHOD_FORM_KEY = 'request_method';

    public readonly string $url;
    public readonly string $urlPath;
    private(set) array $urlParams = [];
    private(set) RequestMethod $method;
    private array $headers;

    public function __construct(
        string        $url,
        RequestMethod $method
    )
    {
        $this->url = trim($url);

        $this->urlPath = $this->parseUrlPath($this->url);

        $this->setGetParamsIfExist($this->url);

        $this->method = $this->getRealMethod($method);
    }

    private function parseUrlPath(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    private function setGetParamsIfExist(string $url): void
    {
        if ($queryString = parse_url($url, PHP_URL_QUERY)) {
            parse_str($queryString, $this->urlParams);
        }
    }

    private function getRealMethod(RequestMethod $actualMethod): RequestMethod
    {
        if ($actualMethod === RequestMethod::POST && $formMethod = (string)$this->getPostArg(self::METHOD_FORM_KEY)) {
            return RequestMethod::tryFromString($formMethod, $actualMethod);
        }

        return $actualMethod;
    }

    /**
     * @return array Headers, array keys - lowercase headers names, array values - headers values
     */
    public function getHeaders(): array
    {
        if (!isset($this->headers)) {
            $this->headers = HeadersUtils::getHeadersFromServerSuperGlobal();
        }

        return $this->headers;
    }

    /**
     * @return mixed Header's value if header is set, $default otherwise.
     */
    public function getHeader(string $name, mixed $default = null): mixed
    {
        $headers = $this->getHeaders();

        return $headers[HeadersUtils::makeLowerCaseAndTrim($name)] ?? $default;
    }

    /**
     * Gets POST key, returns default value if the key is not set.
     */
    public function getPostArg(string $key, mixed $default = null): mixed
    {
        return $this->getPostData()[$key] ?? $default;
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
            (string)$_SERVER['REQUEST_URI'],
            RequestMethod::tryFromString((string)$_SERVER['REQUEST_METHOD'])
        );
    }
}