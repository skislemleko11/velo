<?php
declare(strict_types=1);

namespace Velo\Middlewares;

use Closure;
use Psr\Log\LoggerInterface;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Http\Interfaces\MiddlewareInterface;

readonly class RequestLoggerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ?Closure        $customLogFunction = null,
    )
    {
    }

    public function handle(HttpRequest $request, callable $next): HttpResponse
    {
        if ($this->customLogFunction) {
            ($this->customLogFunction)($request);
        } else {
            $this->logRequestWithLogger($request);
        }

        return $next($request);
    }

    private function logRequestWithLogger(HttpRequest $request): void
    {
        $this->logger->info('Request', [
            'url' => $request->url,
            'method' => $request->method,
        ]);
    }
}