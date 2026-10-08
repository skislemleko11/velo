<?php
declare(strict_types=1);

namespace Velo\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Velo\Container\Container;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\ErrorResponseFormatterInterface;
use Velo\Core\ThrowableHandling\ErrorResponseFormatter\NativeErrorResponseFormatter;
use Velo\Http\Emitter\EmitterInterface;
use Velo\Http\Emitter\NativeEmitter;
use Velo\Http\ResponseRenderer;
use Velo\Http\ResponseRendererInterface;
use Velo\Logger\LogFormatter;
use Velo\Logger\Logger;
use Velo\Logger\NativeLogTextFormatter;
use Velo\Middlewares\Cors\CorsRouterExtension;
use Velo\Router\Router\CorsRouterExtensionInterface;
use Velo\Session\FlashMessages\FlashMessages;
use Velo\Session\FlashMessages\FlashMessagesInterface;
use Velo\Session\Session\Session;
use Velo\Session\Session\SessionInterface;
use Velo\View\ViewRenderer;
use Velo\View\ViewRendererInterface;
use Velo\View\ViewResolver\ViewResolver;
use Velo\View\ViewResolver\ViewResolverInterface;

class DefaultContainerFactory
{
    /**
     * @var array<string, string>
     */
    protected const array DEFAULT_BINDINGS  = [
        ContainerInterface::class => Container::class,
        SessionInterface::class => Session::class,
        FlashMessagesInterface::class => FlashMessages::class,
        EmitterInterface::class => NativeEmitter::class,
        LoggerInterface::class => Logger::class,
        ErrorResponseFormatterInterface::class => NativeErrorResponseFormatter::class,
        ResponseRendererInterface::class => ResponseRenderer::class,
        CorsRouterExtensionInterface::class => CorsRouterExtension::class,
        LogFormatter::class => NativeLogTextFormatter::class,
        ViewRendererInterface::class => ViewRenderer::class,
        ViewResolverInterface::class => ViewResolver::class,
    ];

    public static function create(array $extraBindings = []): Container
    {
        $container = new Container();

        $container->set(Container::class, $container);

        foreach (self::DEFAULT_BINDINGS  as $key => $value) {
            $container->set($key, $value);
        }

        foreach ($extraBindings as $key => $value) {
            $container->set($key, $value);
        }

        return $container;
    }
}