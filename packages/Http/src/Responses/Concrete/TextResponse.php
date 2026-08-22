<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete;

use Velo\Http\RenderContext;
use Velo\Http\ResponseFormat;
use Velo\Http\Responses\Response;

/**
 * Represents an HTTP response containing text content.
 *
 * Defaults to 'text/plain; charset=utf-8' unless another Content-Type is provided in $headers in the constructor.
 */
class TextResponse extends Response
{
    /**
     * @param array $headers Don't forget to pass the 'Content-Type' header if you want it to be different from 'text/plain',
     * which is set if the header is not passed.
     */
    public function __construct(
        private readonly string $content,
        int                     $statusCode = 200,
        array                   $headers = []
    )
    {
        $headers[self::CONTENT_TYPE_HEADER] ??= ResponseFormat::PLAIN_TEXT->value . '; charset=utf-8';

        parent::__construct($statusCode, $headers);
    }

    public function render(RenderContext $context): string
    {
        return $this->content;
    }
}