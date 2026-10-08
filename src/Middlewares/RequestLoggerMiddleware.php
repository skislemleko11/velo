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
final readonly class RequestLoggerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Handles the given Request.
     *
     * @param callable|null $customLogFuntion Provide to handle logging with your own function,
     * it'll be executed with this->logger and request as arguments.
     */
    public function handle(Request $request, callable $next, ?callable $customLogFuntion = null): Response
    {
        if ($customLogFuntion instanceof Closure) {
            ($customLogFuntion)($this->logger, $request);
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