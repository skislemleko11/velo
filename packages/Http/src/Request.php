<?php
declare(strict_types=1);

namespace Velo\Http;

use JsonException;

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

    /**
     * @var array<string, string>
     */
    private(set) array $urlParams = [];
    private(set) RequestMethod $method;

    /**
     * @var array<string, string>
     */
    private array $headers;

    public function __construct(
        string        $url,
        RequestMethod $method
    )
    {
        $this->url = trim($url);

        $this->urlPath = $this->parseUrlPath($this->url);

        $this->setUrlParamsIfExist($this->url);

        $this->method = $this->getRealMethod($method);
    }

    private function parseUrlPath(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    private function setUrlParamsIfExist(string $url): void
    {
        if ($queryString = parse_url($url, PHP_URL_QUERY)) {
            parse_str($queryString, $this->urlParams);
        }
    }

    private function getRealMethod(RequestMethod $actualMethod): RequestMethod
    {
        if ($actualMethod === RequestMethod::POST && $formMethod = (string)$this->getFormValue(self::METHOD_FORM_KEY)) {
            return RequestMethod::tryFromString($formMethod, $actualMethod);
        }

        return $actualMethod;
    }

    public function getContent(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * @throws JsonException
     */
    public function getContentJson(): mixed
    {
        return json_decode(
            $this->getContent(),
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * Gets POST key, returns default value if the key is not set.
     */
    public function getFormValue(string $key, mixed $default = null): mixed
    {
        return $this->getFormData()[$key] ?? $default;
    }

    /**
     * Returns $_POST superglobal.
     *
     * @return array<string, mixed>
     */
    public function getFormData(): array
    {
        return $_POST;
    }

    /**
     * @return array<string, string> Headers, array keys - lowercase headers names, array values - headers values
     */
    public function getHeaders(): array
    {
        if (!isset($this->headers)) {
            $this->headers = HeadersUtils::getHeadersFromServerSuperGlobal();
        }

        return $this->headers;
    }

    /**
     * @return string|null Header's value if header is set, $default otherwise.
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $headers = $this->getHeaders();

        return $headers[HeadersUtils::makeLowerCaseAndTrim($name)] ?? $default;
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