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
    private string $rawInput;
    private mixed $jsonInput;

    public function __construct(
        string                  $url,
        RequestMethod           $method,
        private readonly string $inputStream = 'php://input'
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

    public function getRawInput(): string
    {
        if (!isset($this->rawInput)) {
            $this->rawInput = file_get_contents($this->inputStream) ?: '';
        }

        return $this->rawInput;
    }

    /**
     * @param string|int|null $key Passing null will result in retrievieng all data.
     *
     * @throws JsonException
     */
    public function getJsonInput(string|int|null $key = null, mixed $default = null): mixed
    {
        if (!$rawInput = $this->getRawInput()) {
            return $default;
        }

        if (!isset($this->jsonInput)) {
            $this->jsonInput = json_decode(
                $rawInput,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        }

        if ($key === null) {
            return $this->jsonInput;
        }

        return $this->hasJsonKey($key) ? $this->jsonInput[$key] : $default;
    }

    private function hasJsonKey(string|int $key): bool
    {
        return isset($this->jsonInput) && is_array($this->jsonInput) && array_key_exists($key, $this->jsonInput);
    }

    /**
     * JSON is checked first.
     *
     * @throws JsonException
     */
    public function getJsonInputOrFormValue(string|int $key, mixed $default = null): mixed
    {
        $jsonValue = $this->getJsonInput($key, $default);

        if ($this->hasJsonKey($key)) {
            return $jsonValue;
        }

        return $this->getFormValue((string)$key, $default);
    }

    /**
     * Gets POST key, returns default value if the key is not set.
     */
    public function getFormValue(string $key, mixed $default = null): mixed
    {
        $post = $this->getFormData();

        return array_key_exists($key, $post) ? $post[$key] : $default;
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