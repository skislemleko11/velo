<?php
declare(strict_types=1);

namespace Velo\Http\Responses;

use Velo\Http\RenderContext;

/**
 * Represents an HTTP response. Concrete response classes must extend it.
 */
abstract class Response
{
    public const string CONTENT_TYPE_HEADER = 'Content-Type';

    public function __construct(
        public readonly int $statusCode = 200,
        private(set) array  $headers = []
    )
    {
    }

    abstract public function render(RenderContext $context): string;

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * If the header exists, it will append the value to it. Otherwise, it will create a new header with this value.
     */
    public function appendValueToHeader(string $name, string $value): self
    {
        if (!isset($this->headers[$name])) {
            return $this->setHeader($name, $value);
        }

        if (!preg_match('/(?:^|,\s*)' . preg_quote($value, '/') . '(?:\s*,|$)/', $this->headers[$name])) {
            $this->headers[$name] .= ', ' . $value;
        }

        return $this;
    }
}