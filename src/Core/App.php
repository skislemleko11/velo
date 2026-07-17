<?php
declare(strict_types=1);

namespace Velo\Core;

use SensitiveParameter;
use Velo\Container\Container;
use Velo\Router\Router;
use Velo\Http\ResponseRenderer;

// TODO: Write tests for App class
readonly class App
{
    public function __construct(
        protected Router    $router,
        protected Container $container
    )
    {
    }

    public function run(string $url, string $requestMethod, #[SensitiveParameter] array &$session): void
    {
        if (!isset($session['csrfToken']))
            $session['csrfToken'] = bin2hex(random_bytes(32));

        $response = $this->router->resolve($url, $requestMethod);

        $this->container->get(ResponseRenderer::class)->render($response);
    }
}