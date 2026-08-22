<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete;

use Velo\Http\RenderContext;
use Velo\Http\Responses\Response;

/**
 * Represents a redirect HTTP response.
 */
class RedirectResponse extends Response
{
    public function __construct(
        string $location,
        int    $statusCode = 302,
        array  $headers = []
    )
    {
        $headers['Location'] = $location;

        parent::__construct($statusCode, $headers);
    }

    public function render(RenderContext $context): string
    {
        return '';
    }
}