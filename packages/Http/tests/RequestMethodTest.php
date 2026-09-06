<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RequestMethod;

class RequestMethodTest extends TestCase
{
    /**
     * @param list<string> $stringsToTest
     */
    #[Test]
    #[DataProvider('methodsOfAnyCaseProvider')]
    public function it_gets_the_request_method_from_any_case_string(
        RequestMethod $expectedMethod,
        array         $stringsToTest
    ): void
    {
        foreach ($stringsToTest as $string) {
            $method = RequestMethod::tryFromString($string);

            self::assertSame($expectedMethod, $method);
        }
    }

    /**
     * @return array<string, array{0: RequestMethod, 1: list<string>}>
     */
    public static function methodsOfAnyCaseProvider(): array
    {
        return [
            'GET' => [RequestMethod::GET, ['GET', 'get', 'Get', 'gET']],
            'POST' => [RequestMethod::POST, ['POST', 'post', 'POst', 'poST']],
            'PUT' => [RequestMethod::PUT, ['PUT', 'put', 'Put', 'pUT']],
            'DELETE' => [RequestMethod::DELETE, ['DELETE', 'delete', 'DELete', 'delETE']],
            'PATCH' => [RequestMethod::PATCH, ['PATCH', 'patch', 'PAtch', 'paTCH']],
            'HEAD' => [RequestMethod::HEAD, ['HEAD', 'head', 'HEad', 'heAD']],
            'OPTIONS' => [RequestMethod::OPTIONS, ['OPTIONS', 'options', 'OPTions', 'optiONS']]
        ];
    }

    #[Test]
    public function it_returns_the_given_default_value(): void
    {
        $method = RequestMethod::tryFromString('aaa', RequestMethod::POST);

        self::assertSame(RequestMethod::POST, $method);
    }

    #[Test]
    public function it_returns_unknown_method_when_method_is_not_recognized(): void
    {
        $method = RequestMethod::tryFromString('aaa');

        self::assertSame(RequestMethod::UNKNOWN, $method);
    }
}