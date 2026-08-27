<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete;

use Velo\Http\RenderContext;
use Velo\Http\ResponseFormat;
use Velo\Http\Responses\Response;

/**
 * Represents an HTTP response containing JSON content.
 */
class JsonResponse extends Response
{
    public function __construct(
        public readonly mixed $body,
        int                   $statusCode = 200,
        array                 $headers = [],
        private readonly int  $jsonEncodeFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    )
    {
        $headers[self::CONTENT_TYPE_HEADER] = ResponseFormat::JSON->value;

        parent::__construct($statusCode, $headers);
    }

    public function render(RenderContext $context): string
    {
        return (string)json_encode($this->body, $this->jsonEncodeFlags);
    }
}