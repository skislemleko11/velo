<?php
declare(strict_types=1);

namespace Velo\Core\ThrowableHandling;

use ErrorException;
use JsonException;
use Psr\Log\LoggerInterface;
use Throwable;
use Velo\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseFormat;
use Velo\Http\ResponseRenderer;

/**
 * Throwable handler made for global throwable and error handling.
 */
readonly class ThrowableHandler
{
    public function __construct(
        private LoggerInterface        $logger,
        private ResponseRenderer       $responseRenderer,
        private ErrorResponseFormatter $errorResponseFormatter
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
     * @throws JsonException
     */
    public function handleThrowable(Throwable $throwable): void
    {
        $this->logException($throwable);

        $this->cleanBuffer();

        if (!headers_sent()) {
            $this->responseRenderer->render($this->formatResponse($throwable));
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
     * Uses errorResponseFormatter to format the response based on the Accept header.
     *
     * @throws PathNotFoundException
     */
    private function formatResponse(Throwable $throwable): HttpResponse
    {
        $format = ResponseFormat::fromAcceptHeader($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        return match ($format) {
            ResponseFormat::HTML => $this->errorResponseFormatter->formatView($throwable),
            ResponseFormat::PLAIN_TEXT => $this->errorResponseFormatter->formatPlainText($throwable),
            ResponseFormat::JSON => $this->errorResponseFormatter->formatJson($throwable)
        };
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