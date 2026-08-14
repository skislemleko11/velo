<?php
declare(strict_types=1);

namespace Velo\Core\ThrowableHandling;

use Throwable;
use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\Http\HttpResponse;
use Velo\Router\Exceptions\Interfaces\HttpExceptionInterface;

/**
 * Formats Error Responses.
 *
 * It's used in Throwable Handler to return an aproperiate HttpResponse when an error occurs.
 * Feel free to extend this class and override the format methods to provide custom error response handling.
 */
class ErrorResponseFormatter
{
    public function __construct(protected readonly PathResolver $pathResolver)
    {
    }

    public function formatJson(Throwable $throwable): HttpResponse
    {
        $statusCode = $this->getStatusCode($throwable);

        $result = [
            'error' => [
                'statusCode' => $statusCode,
                'message' => 'An error occurred',
            ]
        ];

        return HttpResponse::json($result, $statusCode);
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

        return HttpResponse::view($this->pathResolver->getFilePath($viewName), $statusCode);
    }

    public function formatPlainText(Throwable $throwable): HttpResponse
    {
        $content = 'An error occurred';

        $statusCode = $this->getStatusCode($throwable);

        return HttpResponse::plainText($content, $statusCode);
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        return $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;
    }
}