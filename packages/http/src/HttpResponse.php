<?php
declare(strict_types=1);

namespace Velo\Http;

readonly class HttpResponse
{
    public function __construct(
        public ?string $viewPath = null,
        public int $statusCode = 200,
        public array $data = [],
        public array $headers = []
    )
    {
    }

    public static function redirect(string $url, int $statuCode = 302): self
    {
        return new self(
            viewPath: null,
            statusCode: $statuCode,
            data: [],
            headers: ['Location' => $url]
        );
    }
}