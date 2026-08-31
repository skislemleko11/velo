<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Closure;
use Psr\Log\LoggerInterface;
use Velo\Http\Request;
use Velo\Http\Responses\Response;
use Velo\Router\Middlewares\MiddlewareInterface;

/**
 * Logs Requests.
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
     * It handles the given Request. If the custom log function was provided in the constructor, calls customLogFunction($request),
     * otherwise calls logRequestWithLogger($request). Then calls next(request).
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->customLogFunction) {
            ($this->customLogFunction)($request);
        } else {
            $this->logRequestWithLogger($request);
        }

        return $next($request);
    }

    /**
     * Logs the given Request with logger:info.
     *
     * Message Format: "Request:\nUrl: {url}\nMethod: {method}"
     * Passes the array from request->url and request->method as context.
     */
    private function logRequestWithLogger(Request $request): void
    {
        $this->logger->info("Request:\nUrl: {url}\nMethod: {method}", [
            'url' => $request->url,
            'url path' => $request->urlPath,
            'method' => $request->method->value,
            'GET params' => $request->urlParams
        ]);
    }
}