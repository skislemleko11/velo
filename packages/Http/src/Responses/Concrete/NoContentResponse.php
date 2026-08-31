<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete;

use Velo\Http\RenderContext;
use Velo\Http\Responses\Response;

/**
 * Represents an HTTP response containing no content.
 */
class NoContentResponse extends Response
{
    public function __construct(int $statusCode = 204, array $headers = [])
    {
        parent::__construct($statusCode, $headers);

        $this->setHeader('Content-Length', '0');
    }

    public function render(RenderContext $context): string
    {
        return '';
    }
}