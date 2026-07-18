<?php
declare(strict_types=1);

namespace Velo\Core;

use SensitiveParameter;
use Velo\Container\Container;
use Velo\Http\HttpRequest;
use Velo\Http\HttpResponse;
use Velo\Router\Router;
use Velo\Http\ResponseRenderer;

readonly class App
{
    public function __construct(
        protected Router    $router,
        protected Container $container
    )
    {
    }

    public function run(HttpRequest $request, #[SensitiveParameter] array &$session): void
    {
        $this->setCsrfToken($session);
        $response = $this->resolve($request);
        $this->renderResponse($response);
    }

    protected function setCsrfToken(#[SensitiveParameter] array &$session): void
    {
        if (!isset($session['csrf_token']))
            $session['csrf_token'] = bin2hex(random_bytes(32));
    }

    protected function resolve(HttpRequest $request): HttpResponse
    {
        return $this->router->resolve($request);
    }

    protected function renderResponse(HttpResponse $response): void
    {
        $this->container->get(ResponseRenderer::class)->render($response);
    }
}