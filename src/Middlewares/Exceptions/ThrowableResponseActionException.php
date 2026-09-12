<?php
declare(strict_types=1);

namespace Velo\Middlewares\Exceptions;

use Throwable;
use Exception;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Exceptions\Interfaces\MiddlewareExceptionInterface;

/**
 * Base exception class allowing to modify the final Response after it's created in ThrowableHandler.
 * Basically a wrapper for the real exception, plus an action on the final Response.
 */
abstract class ThrowableResponseActionException extends Exception implements MiddlewareExceptionInterface
{
    public function __construct(
        private readonly Throwable $baseThrowable
    )
    {
        parent::__construct();
    }

    /**
     * Executes nested actions if there are any, and then the current action.
     */
    final public function execute(Response $response): Response
    {
        if ($this->baseThrowable instanceof ThrowableResponseActionException) {
            $response = $this->baseThrowable->execute($response);
        }

        return $this->executeAction($response);
    }

    /**
     * Implement this method to provide the action, it should return the modified Response,
     * but it can just change the given one and return it ofc.
     */
    abstract protected function executeAction(Response $response): Response;

    final public function getBaseThrowable(): Throwable
    {
        if ($this->baseThrowable instanceof self) {
            return $this->baseThrowable->getBaseThrowable();
        }

        return $this->baseThrowable;
    }
}