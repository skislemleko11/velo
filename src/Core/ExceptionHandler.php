<?php
declare(strict_types=1);

namespace Velo\Core;

use ErrorException;
use Psr\Log\LoggerInterface;
use Throwable;
use Velo\Router\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Router\PathResolver;
use Velo\Http\ResponseRenderer;
use Velo\Http\HttpResponse;

readonly class ExceptionHandler
{
    public function __construct(
        protected LoggerInterface  $logger,
        protected PathResolver     $pathResolver,
        protected ResponseRenderer $responseRenderer
    )
    {
    }

    public function handleException(Throwable $throwable): void
    {
        $this->logException($throwable);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent())
            $this->responseRenderer->render($this->returnResponse($throwable));
        else
            echo 'Critical error occurred! Headers already sent! See logs for details.';
    }

    protected function logException(Throwable $throwable): void
    {
        if ($throwable instanceof ErrorException) {
            $this->logger->error($throwable);
            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            if ($throwable->shouldLogException())
                $this->logger->error($throwable);
            return;
        }

        $this->logger->critical($throwable);
    }

    protected function returnResponse(Throwable $throwable): HttpResponse
    {
        $statusCode = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;

        $viewName = 'error' . $statusCode;

        if (!$this->pathResolver->isFileRegistered($viewName))
            $viewName = 'error500';

        return new HttpResponse($this->pathResolver->getFilePath($viewName), $statusCode);
    }

    public function createErrorException(int $severity, string $message, string $filename, int $line): false
    {
        if (!(error_reporting() & $severity))
            return false;

        throw new ErrorException(
            message: $message,
            code: 0,
            severity: $severity,
            filename: $filename,
            line: $line
        );
    }
}