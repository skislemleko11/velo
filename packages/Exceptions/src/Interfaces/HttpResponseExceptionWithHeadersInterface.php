<?php
declare(strict_types=1);

namespace Velo\Exceptions\Interfaces;

/**
 * Defines an HTTP exception that can provide additional response headers.
 *
 * Extends HttpExceptionInterface with headers that should be included in the
 * HTTP response generated for the exception.
 */
interface HttpResponseExceptionWithHeadersInterface extends HttpResponseExceptionInterface
{
    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;
}