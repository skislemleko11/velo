<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\HttpResponse;

final class HttpResponseTest extends TestCase
{
    #[Test]
    public function it_returns_redirect_response()
    {
        $redirectResponse = HttpResponse::redirect('/login', 333);

        $this->assertInstanceOf(HttpResponse::class, $redirectResponse);
        $this->assertSame(333, $redirectResponse->statusCode);
        $this->assertEquals(['Location' => '/login'], $redirectResponse->headers);
        $this->assertSame(null, $redirectResponse->viewPath);
        $this->assertSame([], $redirectResponse->data);
    }


    #[Test]
    public function it_returns_view_HttpResponse()
    {
        $viewResponse = HttpResponse::view('home', 205, ['hehe'], ['hahah']);

        $this->assertInstanceOf(HttpResponse::class, $viewResponse);
        $this->assertSame('home', $viewResponse->viewPath);
        $this->assertSame(205, $viewResponse->statusCode);
        $this->assertEquals(['hehe'], $viewResponse->data);
        $this->assertEquals(['Content-Type' => 'text/html; charset=utf-8', 'hahah'], $viewResponse->headers);
    }

    #[Test]
    public function it_returns_json_HttpResponse()
    {
        $jsonResponse = HttpResponse::json(['message' => 'Hello, World!'], 205, ['he' => 'he']);

        $this->assertInstanceOf(HttpResponse::class, $jsonResponse);
        $this->assertSame(null, $jsonResponse->viewPath);
        $this->assertSame(205, $jsonResponse->statusCode);
        $this->assertEquals(['message' => 'Hello, World!'], $jsonResponse->data);
        $this->assertEquals(['Content-Type' => 'application/json', 'he' => 'he'], $jsonResponse->headers);
    }

    #[Test]
    public function it_returns_plain_text_HttpResponse()
    {
        $plainTextResponse = HttpResponse::plainText('Hello, World!', 205, ['hihi' => 'hehe']);

        $this->assertInstanceOf(HttpResponse::class, $plainTextResponse);
        $this->assertSame(null, $plainTextResponse->viewPath);
        $this->assertSame('Hello, World!', $plainTextResponse->data);
        $this->assertSame(205, $plainTextResponse->statusCode);
        $this->assertEquals(['hihi' => 'hehe', 'Content-Type' => 'text/plain'], $plainTextResponse->headers);
    }
}