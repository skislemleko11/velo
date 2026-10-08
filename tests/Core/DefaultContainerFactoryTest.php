<?php
declare(strict_types=1);

namespace Velo\Tests\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Velo\Container\Container;
use Velo\Core\DefaultContainerFactory;
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

final class DefaultContainerFactoryTest extends TestCase
{
    private const array DEFAULT_BINDINGS = [
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

    #[Test]
    public function it_creates_a_container_with_default_bindings(): void
    {
        $container = DefaultContainerFactory::create();

        $this->assertHasDefaultBindings($container);
    }

    private function assertHasDefaultBindings(Container $container): void
    {
        self::assertSame($container, $container->get(ContainerInterface::class));

        foreach (self::DEFAULT_BINDINGS as $interface => $concreteClass) {
            self::assertInstanceOf($concreteClass, $container->get($interface));
        }
    }

    #[Test]
    public function it_creates_a_container_with_default_bindings_and_adds_given_ones(): void
    {
        $extraBindings = [
            'hehe' => ContainerInterface::class,
            'hihi' => ViewResolver::class
        ];

        $container = DefaultContainerFactory::create($extraBindings);

        $this->assertHasDefaultBindings($container);
        self::assertSame($container, $container->get('hehe'));
        self::assertInstanceOf(ViewResolver::class, $container->get('hihi'));
    }

    // TODO: ADD A TEST FOR CHANGING THE DEFAULT BINDINGS
}