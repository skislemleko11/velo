<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
        $server = $_SERVER;

        $_SERVER = [
            'HTTP_HOST' => 'example.com',
            'HTTP_CONTENT_TYPE' => ' application/json ',
            'HTTP_X_CUSTOM_HEADER' => ' custom value ',
            'SOME_OTHER_VALUE' => 'should be ignored',
            'SERVER_NAME' => 'example.com',
        ];

        try {
            self::assertSame([
                'host' => 'example.com',
                'content-type' => ' application/json ',
                'x-custom-header' => ' custom value ',
            ], $this->request->getHeaders());
        } finally {
            $_SERVER = $server;
        }
    }

    #[Test]
    public function it_gets_single_header_from_server_super_global(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'SecretValue';

        $request = new Request(self::URL, RequestMethod::GET);

        self::assertSame('SecretValue', $request->getHeader('  x-custom-header'));
        self::assertSame('SecretValue', $request->getHeader(' X-CUSTOM-HEADER   '));
        self::assertSame('default', $request->getHeader('non-existing', 'default'));
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

    #[Test]
    public function it_returns_raw_input_from_input_stream_file(): void
    {
        $content = 'hehe';
        $streamUrl = $this->createStreamWithContent($content);

        $request = new Request(self::URL, RequestMethod::POST, $streamUrl);

        self::assertSame($content, $request->getRawInput());
    }

    private function createStreamWithContent(string $content): string
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return 'data://text/plain;base64,' . base64_encode($content);
    }

    #[Test]
    public function it_returns_empty_string_when_input_file_does_not_exist(): void
    {
        $request = new Request(self::URL, RequestMethod::POST, 'hehe');

        self::assertSame('', @$request->getRawInput());
    }

    #[Test]
    public function it_returns_default_when_there_is_no_content(): void
    {
        self::assertSame('hehe', $this->request->getJsonInput(default: 'hehe'));
    }

    #[Test]
    public function it_returns_all_data_when_no_key_provided(): void
    {
        $data = [
            1 => 1,
            2 => 2,
            4 => 4
        ];
        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertEquals($data, $request->getJsonInput());
    }

    private function createStreamWithContentAndCreateRequest(mixed $data): Request
    {
        $content = is_string($data) ? $data : json_encode($data);
        $streamUrl = $this->createStreamWithContent($content);

        return new Request(self::URL, RequestMethod::POST, $streamUrl);
    }

    #[Test]
    public function it_thorws_json_exception_when_file_is_not_a_valid_json(): void
    {
        $data = '{"name": "Jan", "age": 30';
        $request = $this->createStreamWithContentAndCreateRequest($data);

        $this->expectException(JsonException::class);
        $request->getJsonInput();
    }

    #[Test]
    public function it_returns_default_when_json_is_scalar_and_key_is_provided(): void
    {
        $validJsonScalar = json_encode('hello');

        $streamUrl = $this->createStreamWithContent($validJsonScalar);
        $request = new Request(self::URL, RequestMethod::POST, $streamUrl);

        self::assertSame('hello', $request->getJsonInput(null));
        self::assertSame('default', $request->getJsonInput('some_key', 'default'));
    }

    #[Test]
    public function it_returns_default_when_input_does_not_have_requested_key(): void
    {
        $data = [
            1 => 1,
            2 => 2
        ];
        $request = $this->createStreamWithContentAndCreateRequest($data);
        $default = 'hehe';

        self::assertEquals($default, $request->getJsonInput(3, $default));
    }

    #[Test]
    public function it_returns_null_when_value_is_null(): void
    {
        $data = [1 => null];
        $request = $this->createStreamWithContentAndCreateRequest($data);
        $default = 'hehe';

        self::assertEquals(null, $request->getJsonInput(1, $default));
    }

    #[Test]
    public function it_gets_value_from_json_input(): void
    {
        $data = [1 => 'value'];
        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertEquals('value', $request->getJsonInput(1));
    }

    #[Test]
    public function it_prioritizes_json_over_form_and_gets_json(): void
    {
        $data = [1 => 'value'];
        $_POST[1] = 'nope';

        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertEquals('value', $request->getJsonInputOrFormValue(1));
    }

    #[Test]
    public function it_returns_form_value_when_input_file_does_not_exist(): void
    {
        $_POST[1] = 'value';

        $request = new Request(self::URL, RequestMethod::POST, 'hehe');

        self::assertSame('value', @$request->getJsonInputOrFormValue(1));
    }

    #[Test]
    public function it_returns_form_value_when_there_is_no_content(): void
    {
        $_POST['a'] = 'v';

        self::assertSame('v', $this->request->getJsonInputOrFormValue('a'));
    }

    #[Test]
    public function it_prioritizes_json_and_thorws_json_exception_when_file_is_not_a_valid_json(): void
    {
        $_POST['a'] = 'v';
        $data = '{"a": 30';
        $request = $this->createStreamWithContentAndCreateRequest($data);

        $this->expectException(JsonException::class);
        $request->getJsonInputOrFormValue('a');
    }

    #[Test]
    public function it_returns_form_value_when_input_does_not_have_requested_key(): void
    {
        $_POST['a'] = 'v';
        $data = [1 => 2];

        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertSame('v', $request->getJsonInputOrFormValue('a'));
    }

    #[Test]
    public function it_returns_null_when_input_value_is_null(): void
    {
        $_POST['a'] = 'v';
        $data = ['a' => null];

        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertSame(null, $request->getJsonInputOrFormValue('a'));
    }

    #[Test]
    public function it_returns_null_when_no_json_key_and_form_value_is_null(): void
    {
        $_POST['a'] = null;
        $data = ['b' => null];

        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertSame(null, $request->getJsonInputOrFormValue('a', 'default'));
    }

    #[Test]
    public function it_returns_default_when_both_input_and_form_value_does_not_exist(): void
    {
        unset($_POST['a']);
        $data = ['b' => null];

        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertSame('default', $request->getJsonInputOrFormValue('a', 'default'));
    }

    #[Test]
    public function it_returns_json_value_even_if_it_is_equal_to_default_instead_of_form_value(): void
    {
        $_POST['a'] = 'v';
        $default = 'hehe';
        $data = ['a' => $default];

        $request = $this->createStreamWithContentAndCreateRequest($data);

        self::assertSame($default, $request->getJsonInputOrFormValue('a', $default));
    }
}