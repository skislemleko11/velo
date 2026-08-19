<?php
declare(strict_types=1);

namespace Velo\Core\ThrowableHandling\ErrorResponseFormatter;

use Throwable;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\Interfaces\ErrorResponseFormatterInterface;
use Velo\Exceptions\Interfaces\HttpExceptionInterface;
use Velo\Exceptions\Interfaces\HttpExceptionWithHeadersInterface;
use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\HttpResponse;

/**
 * Formats Error Responses.
 *
 * It's used in Throwable Handler to return an aproperiate HttpResponse when an error occurs.
 * Feel free to extend this class and override the format methods to provide custom error response handling.
 */
class ErrorResponseFormatter implements ErrorResponseFormatterInterface
{
    public const string DEFAULT_ERROR_MESSAGE = 'An error occurred';

    public function __construct(
        protected PathResolver $pathResolver
    )
    {
    }

    public function formatJson(Throwable $throwable): HttpResponse
    {
        $statusCode = $this->getStatusCode($throwable);

        $result = [
            'error' => [
                'statusCode' => $statusCode,
                'message' => $this->getPublicMessage($throwable),
            ]
        ];

        $headers = $this->getHeaders($throwable);

        return HttpResponse::json(
            body: $result,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    /**
     * @throws PathNotFoundException
     */
    public function formatView(Throwable $throwable): HttpResponse
    {
        $statusCode = $this->getStatusCode($throwable);

        $viewName = 'error' . $statusCode;

        if (!$this->pathResolver->isFileRegistered($viewName)) {
            if ($this->pathResolver->isFileRegistered('error')) {
                $viewName = 'error';
            } else {
                return $this->formatPlainText($throwable);
            }
        }

        $headers = $this->getHeaders($throwable);

        return HttpResponse::view(
            viewPath: $this->pathResolver->getFilePath($viewName),
            statusCode: $statusCode,
            headers: $headers
        );
    }

    public function formatPlainText(Throwable $throwable): HttpResponse
    {
        $content = $this->getPublicMessage($throwable);

        $statusCode = $this->getStatusCode($throwable);

        $headers = $this->getHeaders($throwable);

        return HttpResponse::plainText(
            content: $content,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        return $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;
    }

    protected function getPublicMessage(Throwable $throwable): string
    {
        return $throwable instanceof HttpExceptionInterface ? $throwable->getPublicMessage() : self::DEFAULT_ERROR_MESSAGE;
    }

    protected function getHeaders(Throwable $throwable): array
    {
        return $throwable instanceof HttpExceptionWithHeadersInterface ? $throwable->getHeaders() : [];
    }
}