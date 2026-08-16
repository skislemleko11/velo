<?php
declare(strict_types=1);

namespace Velo\Http;

/**
 * Represents an HTTP Response.
 */
readonly class HttpResponse
{
    private function __construct(
        public ?string      $viewPath = null,
        public int          $statusCode = 200,
        public array|string $data = [],
        public array        $headers = []
    )
    {
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
            data: $data,
            headers: $headers
        );
    }

    /**
     * Returns a JSON HttpResponse.
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';

        return new self(
            statusCode: $statusCode,
            data: $data,
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
            data: $content,
            headers: $headers
        );
    }
}