<?php
declare(strict_types=1);

namespace Velo\Core\ThrowableHandling\ErrorResponseFormatter;

use Throwable;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\Interfaces\ErrorResponseFormatterInterface;
use Velo\Exceptions\Interfaces\HttpResponseExceptionInterface;
use Velo\Exceptions\Interfaces\HttpResponseExceptionWithHeadersInterface;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\TextResponse;
use Velo\Http\Responses\Concrete\ViewResponse;

/**
 * Formats Error Responses.
 *
 * It's used in Throwable Handler to return an aproperiate Response when an error occurs.
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

    public function formatJson(Throwable $throwable): JsonResponse
    {
        $statusCode = $this->getStatusCode($throwable);

        $result = [
            'error' => [
                'statusCode' => $statusCode,
                'message' => $this->getPublicMessage($throwable),
            ]
        ];

        $headers = $this->getHeaders($throwable);

        return new JsonResponse(
            body: $result,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    public function formatView(Throwable $throwable): TextResponse|ViewResponse
    {
        $statusCode = $this->getStatusCode($throwable);

        if (($viewFile = $this->pathResolver->resolveErrorFilePath($statusCode)) === false) {
            return $this->formatPlainText($throwable);
        }

        $headers = $this->getHeaders($throwable);

        return new ViewResponse(
            relativeToViewsDirFilePath: $viewFile,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    public function formatPlainText(Throwable $throwable): TextResponse
    {
        $content = $this->getPublicMessage($throwable);

        $statusCode = $this->getStatusCode($throwable);

        $headers = $this->getHeaders($throwable);

        return new TextResponse(
            content: $content,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        return $throwable instanceof HttpResponseExceptionInterface ? $throwable->getStatusCode() : 500;
    }

    protected function getPublicMessage(Throwable $throwable): string
    {
        return $throwable instanceof HttpResponseExceptionInterface ? $throwable->getPublicMessage() : self::DEFAULT_ERROR_MESSAGE;
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(Throwable $throwable): array
    {
        return $throwable instanceof HttpResponseExceptionWithHeadersInterface ? $throwable->getHeaders() : [];
    }
}