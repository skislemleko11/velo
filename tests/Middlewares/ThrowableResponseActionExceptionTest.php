<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;
use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Exceptions\ThrowableResponseActionException;

final class ThrowableResponseActionExceptionTest extends TestCase
{
    #[Test]
    #[DataProvider('nestedThrowablesProvider')]
    public function it_gets_normal_and_nested_base_throwables(Throwable $baseThrowable, ConcreteHeaderAtoB $concrete): void
    {
        self::assertSame($baseThrowable, $concrete->getBaseThrowable());
    }

    /**
     * @return array<string, array{0: Throwable, 1: ConcreteHeaderAtoB}>
     */
    public static function nestedThrowablesProvider(): array
    {
        $baseThrowable = new Exception('hehe');
        $nested_1 = new ConcreteHeaderAtoB($baseThrowable);
        $nested_2 = new ConcreteHeaderAtoB($nested_1);
        $nested_3 = new ConcreteHeaderAtoB($nested_2);
        $nested_4 = new ConcreteHeaderAtoB($nested_3);
        $nested_5 = new ConcreteHeaderAtoB($nested_4);
        $nested_6 = new ConcreteHeaderAtoB($nested_5);

        return [
            'nested_1' => [
                $baseThrowable,
                $nested_1
            ],
            'nested_2' => [
                $baseThrowable,
                $nested_2
            ],
            'nested_3' => [
                $baseThrowable,
                $nested_3
            ],
            'nested_4' => [
                $baseThrowable,
                $nested_4
            ],
            'nested_5' => [
                $baseThrowable,
                $nested_5
            ],
            'nested_6' => [
                $baseThrowable,
                $nested_6
            ]
        ];
    }

    /**
     * @param array<string, string> $expectedHeaders
     */
    #[Test]
    #[DataProvider('executeActionProvider')]
    public function it_executes_nested_action_and_itself(
        ThrowableResponseActionException $concrete,
        NoContentResponse                $baseResponse,
        array                            $expectedHeaders,
    ): void
    {
        $resultResponse = $concrete->execute($baseResponse);

        self::assertSame($baseResponse, $resultResponse);

        foreach ($expectedHeaders as $key => $value) {
            self::assertSame($value, $resultResponse->getHeader($key));
        }
    }

    /**
     * @return array<string, array{0: ThrowableResponseActionException, 1: NoContentResponse, 2: array<string, string>}>
     */
    public static function executeActionProvider(): array
    {
        $baseThrowable = new Exception('hehe');
        $nested_1 = new ConcreteHeaderAtoB($baseThrowable);
        $nested_2 = new ConcreteHeaderBtoA($nested_1);
        $nested_3 = new ConcreteHeaderCtoB($nested_2);
        $nested_4 = new ConcreteHeaderDtoC($nested_3);
        $nested_5 = new ConcreteHeaderEtoD($nested_4);
        $nested_6 = new ConcreteHeaderFtoE($nested_5);

        $baseResponse = new NoContentResponse();

        return [
            'nested_1' => [
                $nested_1,
                $baseResponse,
                [
                    'a' => 'b'
                ]
            ],
            'nested_2' => [
                $nested_2,
                $baseResponse,
                [
                    'a' => 'b',
                    'b' => 'a'
                ]
            ],
            'nested_3' => [
                $nested_3,
                $baseResponse,
                [
                    'a' => 'b',
                    'b' => 'a',
                    'c' => 'b'
                ]
            ],
            'nested_4' => [
                $nested_4,
                $baseResponse,
                [
                    'a' => 'b',
                    'b' => 'a',
                    'c' => 'b',
                    'd' => 'c'
                ]
            ],
            'nested_5' => [
                $nested_5,
                $baseResponse,
                [
                    'a' => 'b',
                    'b' => 'a',
                    'c' => 'b',
                    'd' => 'c',
                    'e' => 'd'
                ]
            ],
            'nested_6' => [
                $nested_6,
                $baseResponse,
                [
                    'a' => 'b',
                    'b' => 'a',
                    'c' => 'b',
                    'd' => 'c',
                    'e' => 'd',
                    'f' => 'e'
                ]
            ]
        ];
    }

    #[Test]
    public function it_executes_the_most_nested_first_so_the_least_nested_override_the_others(): void
    {
        $baseThrowable = new Exception('hehe');
        $concrete = new ConcreteHeaderAtoD(
            new ConcreteHeaderAtoC(
                new ConcreteHeaderAtoB(
                    new ConcreteHeaderCtoD(
                        new ConcreteHeaderCtoB(
                            $baseThrowable
                        )
                    )
                )
            )
        );
        $baseResponse = new NoContentResponse();

        $resultResponse = $concrete->execute($baseResponse);

        self::assertSame('d', $resultResponse->getHeader('a'));
        self::assertSame('d', $resultResponse->getHeader('c'));
    }
}

final class ConcreteHeaderAtoB extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('a', 'b');
    }
}

final class ConcreteHeaderAtoC extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('a', 'c');
    }
}

final class ConcreteHeaderAtoD extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('a', 'd');
    }
}

final class ConcreteHeaderBtoA extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('b', 'a');
    }
}

final class ConcreteHeaderCtoB extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('c', 'b');
    }
}

final class ConcreteHeaderCtoD extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('c', 'd');
    }
}

final class ConcreteHeaderDtoC extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('d', 'c');
    }
}

final class ConcreteHeaderEtoD extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('e', 'd');
    }
}

final class ConcreteHeaderFtoE extends ThrowableResponseActionException
{
    protected function executeAction(Response $response): Response
    {
        return $response->setHeader('f', 'e');
    }
}