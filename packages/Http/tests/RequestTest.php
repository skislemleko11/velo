<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;
use Velo\Http\Request;
use Velo\Http\RequestMethod;

final class RequestTest extends TestCase
{
    private const string URL = 'https://example.com/hehe/hihi';
    private Request $request;

    protected function setUp(): void
    {
        $this->request = new Request(self::URL, RequestMethod::GET);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SERVER = [];
    }

    #[Test]
    public function it_parsed_url_in_constructor(): void
    {
        $this->assertSame('/hehe/hihi', $this->request->urlPath);
        $this->assertEmpty($this->request->getParams);
    }

    #[Test]
    public function it_parses_url_query_parameters(): void
    {
        $request = new Request('https://example.com/search?q=velo&page=2', RequestMethod::GET);

        $this->assertSame('/search', $request->urlPath);
        $this->assertSame(['q' => 'velo', 'page' => '2'], $request->getParams);
    }

    #[Test]
    public function it_overrides_method_from_post_form_key(): void
    {
        $_POST[Request::METHOD_FORM_KEY] = 'PUT';

        $request = new Request('https://example.com/resource', RequestMethod::POST);

        $this->assertSame(RequestMethod::PUT, $request->method);
    }

    #[Test]
    public function it_sets_headers_from_server_super_global(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        $request = new Request('https://example.com/resource', RequestMethod::GET);

        $this->assertSame('example.com', $request->headers['Host']);
        $this->assertSame('Mozilla/5.0', $request->headers['User-Agent']);
        $this->assertSame('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', $request->headers['Accept']);
    }

    #[Test]
    public function it_gets_post_arg_value(): void
    {
        $_POST['key'] = 'value';
        $this->assertSame('value', $this->request->getPostArg('key'));
    }

    #[Test]
    public function it_gets_post_arg_default_null(): void
    {
        unset($_POST['key']);
        $this->assertNull($this->request->getPostArg('key'));
    }

    #[Test]
    public function it_gets_post_arg_default(): void
    {
        unset($_POST['key']);
        $this->assertSame('value', $this->request->getPostArg('key', 'value'));
    }

    #[Test]
    public function it_gets_post_data(): void
    {
        $_POST = ['hehe' => 'hihi', 'key' => 'value'];
        $this->assertSame($_POST, $this->request->getPostData());
    }

    #[Test]
    public function it_changes_method_from_head_to_get(): void
    {
        $request = new Request(self::URL, RequestMethod::HEAD);
        $result = $request->changeMethodFromHeadToGet();

        $this->assertSame(RequestMethod::GET, $request->method);
        $this->assertSame($request, $result);
    }

    #[Test]
    #[DataProvider('nonHeadMethodsProvider')]
    public function it_throws_value_error_when_changing_method_from_non_head(RequestMethod $method): void
    {
        $request = new Request(self::URL, $method);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessageIs("Cannot change HTTP request method: $method->value from get, because it is not HEAD.");

        $request->changeMethodFromHeadToGet();
    }

    public static function nonHeadMethodsProvider(): array
    {
        return array_map(
            fn(RequestMethod $method) => [$method],
            array_filter(RequestMethod::cases(), fn(RequestMethod $m) => $m !== RequestMethod::HEAD)
        );
    }

    #[Test]
    public function it_creates_instance_from_globals(): void
    {
        $_SERVER['REQUEST_URI'] = '/dashboard?ref=mail';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = Request::fromGlobals();

        $this->assertSame('/dashboard?ref=mail', $request->url);
        $this->assertSame('/dashboard', $request->urlPath);
        $this->assertSame(['ref' => 'mail'], $request->getParams);
        $this->assertSame(RequestMethod::GET, $request->method);
    }
}