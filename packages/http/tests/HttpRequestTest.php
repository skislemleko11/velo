<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\Test;
use Velo\Http\HttpRequest;
use PHPUnit\Framework\TestCase;

class HttpRequestTest extends TestCase
{
    private const string URL = 'https://example.com/hehe/hihi';
    private HttpRequest $httpRequest;

    protected function setUp(): void
    {
        $this->httpRequest = new HttpRequest(self::URL, 'get');
    }

    #[Test]
    public function it_parsed_url_in_constructor(): void
    {
        $this->assertSame(parse_url(self::URL, PHP_URL_PATH), $this->httpRequest->url);
    }

    #[Test]
    public function it_made_method_name_uppercase(): void
    {
        $this->assertSame(strtoupper($this->httpRequest->method), $this->httpRequest->method);
    }

    #[Test]
    public function it_gets_post_arg_value(): void
    {
        $_POST['key'] = 'value';
        $this->assertSame('value', $this->httpRequest->getPostArg('key'));
    }

    #[Test]
    public function it_gets_post_arg_default_null(): void
    {
        unset($_POST['key']);
        $this->assertNull($this->httpRequest->getPostArg('key'));
    }

    #[Test]
    public function it_gets_post_arg_default(): void
    {
        unset($_POST['key']);
        $this->assertSame('value', $this->httpRequest->getPostArg('key', 'value'));
    }

    #[Test]
    public function it_gets_post_data(): void
    {
        $_POST = ['hehe' => 'hihi', 'key' => 'value'];
        $this->assertSame($_POST, $this->httpRequest->getPostData());
    }
}
