<?php
declare(strict_types=1);

namespace Velo\Middlewares\Exceptions;

use Throwable;
use Exception;
use Velo\Http\Responses\Response;

abstract class ThrowableResponseActionException extends Exception
{
    public function __construct(
        private readonly Throwable $baseThrowable
    )
    {
        parent::__construct();
    }

    final public function execute(Response $response): Response
    {
        if ($this->baseThrowable instanceof ThrowableResponseActionException) {
            $response = $this->baseThrowable->execute($response);
        }

        return $this->executeAction($response);
    }

    abstract protected function executeAction(Response $response): Response;

    final public function getBaseThrowable(): Throwable
    {
        if ($this->baseThrowable instanceof self) {
            return $this->baseThrowable->getBaseThrowable();
        }

        return $this->baseThrowable;
    }
}