<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Request;
use Velo\Http\RequestMethod;
use Velo\Http\Responses\Response;

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
        self::assertSame('/hehe/hihi', $this->request->urlPath);
        self::assertEmpty($this->request->urlParams);
    }

    #[Test]
    public function it_trims_url(): void
    {
        $request = new Request('     spaces.com  ', RequestMethod::GET);

        self::assertSame('spaces.com', $request->url);
    }

    #[Test]
    public function it_parses_url_query_parameters(): void
    {
        $request = new Request('  https://example.com/search?q=velo&page=2', RequestMethod::GET);

        self::assertSame('/search', $request->urlPath);
        self::assertSame(['q' => 'velo', 'page' => '2'], $request->urlParams);
    }

    #[Test]
    public function it_overrides_method_from_post_form_key(): void
    {
        $_POST[Request::METHOD_FORM_KEY] = 'PUT';

        $request = new Request('https://example.com/resource', RequestMethod::POST);

        self::assertSame(RequestMethod::PUT, $request->method);
    }

    #[Test]
    public function it_sets_headers_from_server_super_global(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        $request = new Request('https://example.com/resource', RequestMethod::GET);

        self::assertSame('example.com', $request->getHeaders()['host']);
        self::assertSame('Mozilla/5.0', $request->getHeaders()['user-agent']);
        self::assertSame('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', $request->getHeaders()['accept']);
    }

    #[Test]
    public function it_gets_form_value(): void
    {
        $_POST['key'] = 'value';
        self::assertSame('value', $this->request->getFormValue('key'));
    }

    #[Test]
    public function it_gets_form_value_default_null(): void
    {
        unset($_POST['key']);
        self::assertNull($this->request->getFormValue('key'));
    }

    #[Test]
    public function it_gets_form_value_default(): void
    {
        unset($_POST['key']);
        self::assertSame('value', $this->request->getFormValue('key', 'value'));
    }

    #[Test]
    public function it_gets_post_data(): void
    {
        $_POST = ['hehe' => 'hihi', 'key' => 'value'];
        self::assertSame($_POST, $this->request->getFormData());
    }

    #[Test]
    public function it_gets_headers(): void
    {
        $response = $this->getResponseWithHeaders();

        self::assertEquals(['hehe' => 'hihi', 'a' => 'b', 'c' => 'D'], $response->getHeaders());
    }

    private function getResponseWithHeaders(): Response
    {
        return new class(200, ['hehe' => 'hihi', 'a' => 'b', 'C    ' => 'D']) extends Response {
            public function render(RenderContext $context): string
            {
                return '';
            }
        };
    }

    #[Test]
    public function it_gets_header(): void
    {
        $response = $this->getResponseWithHeaders();

        self::assertEquals('hihi', $response->getHeader('hehe'));
        self::assertEquals('b', $response->getHeader('A    '));
        self::assertEquals('D', $response->getHeader('c   '));
    }

    #[Test]
    public function it_casts_method_form_value_to_string(): void
    {
        $_POST[Request::METHOD_FORM_KEY] = 1;

        $request = new Request(self::URL, RequestMethod::POST);

        self::assertSame(RequestMethod::POST, $request->method);
    }

    #[Test]
    public function it_creates_instance_from_globals(): void
    {
        $_SERVER['REQUEST_URI'] = '/dashboard?ref=mail';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = Request::fromGlobals();

        self::assertSame('/dashboard?ref=mail', $request->url);
        self::assertSame('/dashboard', $request->urlPath);
        self::assertSame(['ref' => 'mail'], $request->urlParams);
        self::assertSame(RequestMethod::GET, $request->method);
    }

    #[Test]
    public function it_casts_request_uri_and_method_to_string(): void
    {
        $_SERVER['REQUEST_URI'] = 1;
        $_SERVER['REQUEST_METHOD'] = 1;

        $request = Request::fromGlobals();

        self::assertSame('1', $request->url);
        self::assertSame(RequestMethod::tryFromString('1'), $request->method);
    }

    #[Test]
    public function it_reurns_unknown_method_when_method_is_not_recognized(): void
    {
        $request = new Request(self::URL, RequestMethod::tryFromString('NOT_A_VALID_METHOD'));

        self::assertSame(RequestMethod::UNKNOWN, $request->method);
    }
}