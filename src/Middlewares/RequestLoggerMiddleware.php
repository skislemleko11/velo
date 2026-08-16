<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Closure;
use Psr\Log\LoggerInterface;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Router\Middlewares\MiddlewareInterface;

/**
 * Logs HttpRequests.
 */
readonly class RequestLoggerMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure|null $customLogFunction Should take a request.
     */
    public function __construct(
        private LoggerInterface $logger,
        private ?Closure        $customLogFunction = null,
    )
    {
    }

    /**
     * It handles the given HttpRequest. If the custom log function was provided in the constructor, calls customLogFunction($request),
     * otherwise calls logRequestWithLogger($request). Then calls next(request).
     */
    public function handle(HttpRequest $request, callable $next): HttpResponse
    {
        if ($this->customLogFunction) {
            ($this->customLogFunction)($request);
        } else {
            $this->logRequestWithLogger($request);
        }

        return $next($request);
    }

    /**
     * Logs the given HttpRequest with logger:info.
     *
     * Message Format: "Request:\nUrl: {url}\nMethod: {method}"
     * Passes the array from request->url and request->method as context.
     */
    private function logRequestWithLogger(HttpRequest $request): void
    {
        $this->logger->info("Request:\nUrl: {url}\nMethod: {method}", [
            'url' => $request->url,
            'url path' => $request->urlPath,
            'method' => $request->method->value,
            'GET params' => $request->getParams
        ]);
    }
}