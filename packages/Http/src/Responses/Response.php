<?php
declare(strict_types=1);

namespace Velo\Http\Responses;

use Velo\Http\HeadersUtils;
use Velo\Http\RenderContext;

/**
 * Represents an HTTP response. Concrete response classes must extend it.
 */
abstract class Response
{
    public const string CONTENT_TYPE_HEADER = 'content-type';

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $statusCode = 200,
        array               $headers = []
    )
    {
        $this->setHeaders($headers);
    }

    abstract public function render(RenderContext $context): string;

    /**
     * @return string|null Header's value if header is set, $default otherwise.
     */
    public function getHeader(string $name, ?string $default = null): string|null
    {
        $headers = $this->getHeaders();

        return $headers[HeadersUtils::makeLowerCaseAndTrim($name)] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name Will be converted to lowercase and trimmed.
     * @param string $value Will be trimmed.
     */
    public function setHeader(string $name, string $value): self
    {
        $name = HeadersUtils::makeLowerCaseAndTrim($name);
        $value = trim($value);

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param array<string, string> $headers Keys - headers names will be converted to lowercase and trimmed,
     * values - headers values will be trimmed.
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * If the header exists, it will append the value to it. Otherwise, it will create a new header with this value.
     *
     * @param string $name Will be converted to lowercase and trimmed.
     * @param string $value Will be trimmed.
     */
    public function appendValueToHeader(string $name, string $value): self
    {
        $value = trim($value);
        $currentValue = $this->getHeader($name);

        if ($currentValue === null) {
            return $this->setHeader($name, $value);
        }

        if (!preg_match('/(?:^|,\s*)' . preg_quote($value, '/') . '(?:\s*,|$)/', $currentValue)) {
            $this->setHeader($name, $currentValue . ', ' . $value);
        }

        return $this;
    }
}