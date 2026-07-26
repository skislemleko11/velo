<?php
declare(strict_types=1);

namespace Velo\Core;

use ErrorException;
use Psr\Log\LoggerInterface;
use Throwable;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;
use Velo\Router\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Router\PathResolver\Exceptions\PathNotFoundException;
use Velo\Router\PathResolver\PathResolver;

/**
 * Throwable handler made for global throwable and error handling.
 */
readonly class ThrowableHandler
{
    public function __construct(
        protected LoggerInterface  $logger,
        protected PathResolver     $pathResolver,
        protected ResponseRenderer $responseRenderer
    )
    {
    }

    /**
     * Handles the given Throwable.
     *
     * It logs exceptions if it's meant to be logged,
     * cleans the buffer and renders aproperiate resopone using returnResponse method and ResponseRenderer render method.
     *
     * @throws PathNotFoundException
     */
    public function handleThrowable(Throwable $throwable): void
    {
        $this->logException($throwable);

        $this->cleanBuffer();

        if (!headers_sent()) {
            $this->responseRenderer->render($this->returnResponse($throwable));
        } else {
            echo 'Critical error occurred! Headers already sent!';
        }
    }

    /**
     * Logs Throwable if it's meant to be logged.
     *
     * Throwable is meant to be logged if it's an instance of ErrorException,
     * or it's an instance of HttpExceptionInterface and shouldLogException() returns true
     */
    private function logException(Throwable $throwable): void
    {
        if ($throwable instanceof ErrorException) {
            $this->logger->error($throwable);
            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            if ($throwable->shouldLogException()) {
                $this->logger->error($throwable);
            }
            return;
        }

        $this->logger->critical($throwable);
    }

    /**
     * Returns HttpResponse for the given Throwable.
     *
     * @throws PathNotFoundException
     */
    private function returnResponse(Throwable $throwable): HttpResponse
    {
        $statusCode = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;

        $viewName = 'error' . $statusCode;

        if (!$this->pathResolver->isFileRegistered($viewName)) {
            $viewName = 'error500';
        }

        return new HttpResponse($this->pathResolver->getFilePath($viewName), $statusCode);
    }

    /**
     * Throws an ErrorException instance created from the given arguments.
     *
     * Returns false if error_reporting is not active for the given severity.
     *
     * @throws ErrorException
     */
    public function throwErrorException(int $severity, string $message, string $filename, int $line): false
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException(
            message: $message,
            code: 0,
            severity: $severity,
            filename: $filename,
            line: $line
        );
    }

    /**
     * Cleans the buffer to the top level.
     */
    private function cleanBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}