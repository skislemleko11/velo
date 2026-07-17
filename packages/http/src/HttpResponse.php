<?php
declare(strict_types=1);

namespace Velo\Http;

readonly class HttpResponse
{
    public function __construct(
        public ?string $viewPath = null,
        public int $statusCode = 200,
        public array $data = [],
    )
    {
    }
}