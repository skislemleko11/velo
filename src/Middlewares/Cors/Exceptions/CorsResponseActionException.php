<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors\Exceptions;

use Throwable;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Cors\CorsResponseProcessor;
use Velo\Middlewares\Exceptions\ThrowableResponseActionException;

/**
 * Adds CORS headers to the final Response.
 */
final class CorsResponseActionException extends ThrowableResponseActionException
{
    public function __construct(
        private readonly CorsResponseProcessor $processor,
        Throwable                              $baseThrowable
    )
    {
        parent::__construct($baseThrowable);
    }

    protected function executeAction(Response $response): Response
    {
        return $this->processor->addCorsHeaders($response);
    }
}