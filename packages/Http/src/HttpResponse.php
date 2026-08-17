<?php
declare(strict_types=1);

namespace Velo\Http;

/**
 * Represents an HTTP Response.
 */
class HttpResponse
{
    private function __construct(
        public readonly ?string      $viewPath = null,
        public readonly int          $statusCode = 200,
        public readonly array|string $body = [],
        private(set) array           $headers = []
    )
    {
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Returns a redirect HttpResponse.
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self(
            statusCode: $statusCode,
            headers: ['Location' => $url]
        );
    }

    /**
     * Returns a View HttpResponse.
     */
    public static function view(string $viewPath, int $statusCode = 200, array $data = [], array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new self(
            viewPath: $viewPath,
            statusCode: $statusCode,
            body: $data,
            headers: $headers
        );
    }

    /**
     * Returns a JSON HttpResponse.
     */
    public static function json(array $body, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';

        return new self(
            statusCode: $statusCode,
            body: $body,
            headers: $headers
        );
    }

    /**
     * Returns a Plain text HttpResponse.
     */
    public static function plainText(string $content, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/plain';

        return new self(
            statusCode: $statusCode,
            body: $content,
            headers: $headers
        );
    }
}