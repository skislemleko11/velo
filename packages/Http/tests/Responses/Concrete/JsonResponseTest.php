<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses\Concrete;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\JsonResponse;

final class JsonResponseTest extends TestCase
{
    private RenderContext $context;

    protected function setUp(): void
    {
        $this->context = $this->createStub(RenderContext::class);
    }

    #[Test]
    public function it_sets_content_type_to_json(): void
    {
        $response = new JsonResponse(['foo' => 'bar'], headers: ['Content-Type' => 'ahshhd', 'hehe' => 'haha']);

        $this->assertEquals(['Content-Type' => 'application/json', 'hehe' => 'haha'], $response->headers);
    }

    #[Test]
    public function it_returns_json(): void
    {
        $arr = ['foo' => 'abar'];
        $response = new JsonResponse($arr);

        $this->assertEquals(
            json_encode($arr),
            $response->render($this->context)
        );
    }

    #[Test]
    public function it_returns_json_with_unescaped_unicode_by_default(): void
    {
        $arr = ['foo' => 'ąbąr'];
        $response = new JsonResponse($arr);

        $this->assertEquals(
            json_encode($arr, JSON_UNESCAPED_UNICODE),
            $response->render($this->context)
        );
    }

    #[Test]
    public function it_throws_on_error_by_default(): void
    {
        $this->expectException(JsonException::class);

        $response = new JsonResponse(['hehe' => INF]);
        $response->render($this->context);
    }

    #[Test]
    public function it_uses_json_encode_provided_flags(): void
    {
        $response = new JsonResponse(['foo/' => 'bar'], jsonEncodeFlags: JSON_UNESCAPED_SLASHES);

        $this->assertEquals(json_encode(['foo/' => 'bar'], JSON_UNESCAPED_SLASHES), $response->render($this->context));
    }

    #[Test]
    public function it_renders_empty_string_on_json_encode_false_result(): void
    {
        $data = "\xB1";

        $respone = new JsonResponse($data, jsonEncodeFlags: JSON_ERROR_NONE);

        $this->assertSame('', $respone->render($this->context));
    }
}