<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\Cors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Response;
use Velo\Http\Tests\RequestMethodTest;
use Velo\Middlewares\Cors\CorsMiddleware;
use Velo\Middlewares\Cors\CorsRouterExtension;
use Velo\Middlewares\Cors\Headers\CorsRequestHeaderName;
use Velo\Router\Middlewares\MiddlewareInterface;
use Velo\Router\Route;

final class CorsRouterExtensionTest extends TestCase
{
    private const string ACCESS_CONTROL_REQUEST_METHOD_HEADER = 'HTTP_ACCESS_CONTROL_REQUEST_METHOD';

    private CorsRouterExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new CorsRouterExtension();
    }

    #[Test]
    public function it_detects_preflight_request(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertTrue(
            $this->extension->isPreflight($request),
        );
    }

    #[Test]
    public function it_does_not_detect_preflight_request_when_method_is_not_options(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::POST,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertFalse(
            $this->extension->isPreflight($request),
        );
    }

    #[Test]
    public function it_does_not_detect_preflight_request_when_origin_is_missing(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::REQUEST_METHOD->value => 'POST',
            ],
        );

        self::assertFalse(
            $this->extension->isPreflight($request),
        );
    }

    #[Test]
    public function it_does_not_detect_preflight_request_when_requested_method_is_missing(): void
    {
        $request = CorsTestsUtils::createRequest(
            RequestMethod::OPTIONS,
            [
                CorsRequestHeaderName::ORIGIN->value => 'https://example.com',
            ],
        );

        self::assertFalse(
            $this->extension->isPreflight($request),
        );
    }

    #[Test]
    public function it_returns_unknown_request_method_when_there_is_no_header(): void
    {
        unset($_SERVER[self::ACCESS_CONTROL_REQUEST_METHOD_HEADER]);
        $request = new Request('', RequestMethod::OPTIONS);

        self::assertSame(
            RequestMethod::UNKNOWN,
            $this->extension->getRequestedMethodFromPreflight($request)
        );
    }

    /**
     * @param list<string> $stringsToTest
     */
    #[Test]
    #[DataProvider('methodsOfAnyCaseProvider')]
    public function it_returns_the_same_as_try_from_string_function_from_request_method_enum(
        RequestMethod $expectedMethod,
        array         $stringsToTest
    ): void
    {
        $_SERVER[self::ACCESS_CONTROL_REQUEST_METHOD_HEADER] = $expectedMethod->value;

        $request = new Request('', RequestMethod::OPTIONS);

        self::assertSame(
            $expectedMethod,
            $this->extension->getRequestedMethodFromPreflight($request));
    }

    /**
     * @return array<string, array{0: RequestMethod, 1: list<string>}>
     */
    public static function methodsOfAnyCaseProvider(): array
    {
        return RequestMethodTest::methodsOfAnyCaseProvider();
    }

    #[Test]
    #[DataProvider('routesWithCorsMiddlewareInstancesProvider')]
    public function it_returns_true_when_there_is_cors_middleware_instance(array $middlewaresInstances): void
    {
        $route = self::createRoute($middlewaresInstances);

        self::assertTrue($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @return array<string, array{0: list<MiddlewareInterface>}>
     */
    public static function routesWithCorsMiddlewareInstancesProvider(): array
    {
        $corsMiddleware = new CorsMiddleware();
        $testMiddleware1 = self::getRandomMiddlewareInstance();
        $testMiddleware2 = self::getRandomMiddlewareInstance();

        return [
            'cors_middleware_first' => [
                [
                    $corsMiddleware,
                    $testMiddleware1
                ]
            ],
            'cors_middleware_second' => [
                [
                    $testMiddleware2,
                    $corsMiddleware,
                    $testMiddleware1
                ]
            ],
            'cors_middleware_third' => [
                [
                    $testMiddleware1,
                    $testMiddleware2,
                    $corsMiddleware
                ]
            ],
            'only_cors_middleware' => [
                [
                    $corsMiddleware
                ]
            ]
        ];
    }

    private static function getRandomMiddlewareInstance(): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request);
            }
        };
    }

    #[Test]
    #[DataProvider('routesWithCorsMiddlewareNameProvider')]
    public function it_returns_true_when_there_is_fully_qualified_cors_middleware_name(array $middlewares): void
    {
        $route = self::createRoute($middlewares);

        self::assertTrue($this->extension->hasCorsMiddleware($route));
    }


    /**
     * @return array<string, list<string>>
     */
    public static function routesWithCorsMiddlewareNameProvider(): array
    {
        return [
            'cors_middleware_first' => [
                [
                    CorsMiddleware::class,
                    'hehe',
                    'hihi'
                ]
            ],
            'cors_middleware_second' => [
                [
                    '',
                    CorsMiddleware::class,
                    'hihi'
                ]
            ],
            'cors_middleware_third' => [
                [
                    'a',
                    'f',
                    CorsMiddleware::class
                ]
            ],
            'only_cors_middleware' => [
                [
                    CorsMiddleware::class
                ]
            ]
        ];
    }

    #[Test]
    #[DataProvider('routesWithArrayAsMiddlewareProvider')]
    public function it_returns_true_when_there_is_array_with_cors_middleware(array $middlewares): void
    {
        $route = self::createRoute($middlewares);

        self::assertTrue($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @return array<string, list<list<mixed>>>
     */
    public static function routesWithArrayAsMiddlewareProvider(): array
    {
        return [
            'cors_middleware_first' => [
                [
                    [CorsMiddleware::class, ['hehe']],
                    'hehe',
                    'hihi'
                ]
            ],
            'cors_middleware_second' => [
                [
                    '',
                    [CorsMiddleware::class, ['hehe']],
                    'hihi'
                ]
            ],
            'cors_middleware_third' => [
                [
                    'a',
                    'f',
                    [CorsMiddleware::class, ['hehe', 'hihi', 'hah']]
                ]
            ],
            'only_cors_middleware' => [
                [
                    [CorsMiddleware::class]
                ]
            ],
            'cors_middleware_with_empty_array' => [
                [
                    [CorsMiddleware::class, []]
                ]
            ]
        ];
    }

    #[Test]
    public function it_returns_false_when_array_is_too_long(): void
    {
        $route = self::createRoute(
            [
                [CorsMiddleware::class, ['hehe'], 'hihi']
            ]
        );

        self::assertFalse($this->extension->hasCorsMiddleware($route));
    }

    #[Test]
    public function it_returns_false_when_there_are_no_middlewares(): void
    {
        $route = self::createRoute([]);

        self::assertFalse($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @param list<mixed> $notMiddleware
     */
    #[Test]
    #[DataProvider('firstElementIsNotCorsMiddlewareNameProvider')]
    public function it_returns_false_when_first_element_in_array_is_not_cors_middleware_name(array $notMiddleware): void
    {
        $route = self::createRoute(
            [
                $notMiddleware
            ]
        );

        self::assertFalse($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @return array<string, array{0: list<mixed>}>
     */
    public static function firstElementIsNotCorsMiddlewareNameProvider(): array
    {
        return [
            'random_string_given' => [
                ['hehe']
            ],
            'array_given' => [
                [[], ['hehe']]
            ],
            'object_given' => [
                [new stdClass(), ['hehe']]
            ],
            'int_given' => [
                [12, ['a']]
            ],
            'float_given' => [
                [12.3, ['a']]
            ],
            'true_given' => [
                [true, ['a']]
            ],
            'false_given' => [
                [false, ['a']]
            ],
            'null_given' => [
                [null, ['a']]
            ]
        ];
    }

    #[Test]
    #[DataProvider('secondElementIsNotArrayProvider')]
    public function it_returns_false_when_second_element_is_not_array(mixed $secondElement): void
    {
        $route = self::createRoute(
            [
                [CorsMiddleware::class, $secondElement]
            ]
        );

        self::assertFalse($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function secondElementIsNotArrayProvider(): array
    {
        return [
            'string_given' => [
                'hehe'
            ],
            'object_given' => [
                new stdClass()
            ],
            'int_given' => [
                12
            ],
            'float_given' => [
                12.3
            ],
            'true_given' => [
                true
            ],
            'false_given' => [
                false
            ],
            'null_given' => [
                null
            ]
        ];
    }

    #[Test]
    #[DataProvider('notCorsMiddlewareNameProvider')]
    public function it_returns_false_when_given_value_is_not_full_cors_middleware_name(
        string $notCorsMiddlewareFullName
    ): void
    {
        $route = self::createRoute(
            [
                $notCorsMiddlewareFullName
            ]
        );

        self::assertFalse($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function notCorsMiddlewareNameProvider(): array
    {
        return [
            'not_full_cors_middleware_name_string_given' => [
                'CorsMiddleware'
            ],
            'hehe_string_given' => [
                'hehe'
            ],
            'idk_string_given' => [
                'idk'
            ],
            'hihi_string_given' => [
                'hihi'
            ]
        ];
    }

    #[Test]
    public function it_returns_false_when_given_middleware_instance_is_not_cors_middleware_instance(): void
    {
        $route = self::createRoute(
            [
                self::getRandomMiddlewareInstance()
            ]
        );

        self::assertFalse($this->extension->hasCorsMiddleware($route));
    }

    /**
     * @param list<string|array{0: string, 1?: list<mixed>}|MiddlewareInterface|callable> $middlewares
     */
    private static function createRoute(array $middlewares): Route
    {
        $route = new Route(
            requestMethod: RequestMethod::GET,
            path: '',
            controller: '',
            action: ''
        );

        foreach ($middlewares as $middleware) {
            $route->addMiddleware($middleware);
        }

        return $route;
    }
}