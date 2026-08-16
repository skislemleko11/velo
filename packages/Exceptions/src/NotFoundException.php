<?php
declare(strict_types=1);

namespace Velo\Exceptions;

use Exception;
use Velo\Exceptions\Interfaces\HttpExceptionInterface;

/**
 * Base exception for resources that cannot be found.
 *
 * Represents an HTTP 404 Not Found error and provides the default public
 * message for this type of response.
 */
abstract class NotFoundException extends Exception implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 404;
    }

    public function getPublicMessage(): string
    {
        return 'Resource not found!';
    }

    public function shouldLogException(): bool
    {
        return false;
    }
}