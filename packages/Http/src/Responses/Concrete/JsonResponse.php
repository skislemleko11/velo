<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete;

use JsonException;
use Velo\Http\RenderContext;
use Velo\Http\ResponseFormat;
use Velo\Http\Responses\Response;

/**
 * Represents an HTTP response containing JSON content.
 */
class JsonResponse extends Response
{
    public function __construct(
        public readonly array $body,
        int                   $statusCode = 200,
        array                 $headers = []
    )
    {
        $headers[self::CONTENT_TYPE_HEADER] = ResponseFormat::JSON->value;

        parent::__construct($statusCode, $headers);
    }

    /**
     * @throws JsonException
     */
    public function render(RenderContext $context): string
    {
        return json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}