<?php
declare(strict_types=1);

namespace Velo\Exceptions\Interfaces;

/**
 * Defines an exception that can be translated into an HTTP response.
 *
 * Implementations provide the HTTP status code and public message that should
 * be exposed to the client, as well as whether the exception should be logged.
 */
interface HttpExceptionInterface
{
    public function getStatusCode(): int;

    public function shouldLogException(): bool;

    public function getPublicMessage(): string;
}