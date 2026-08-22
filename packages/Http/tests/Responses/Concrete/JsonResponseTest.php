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
        $arr = ['foo' => 'ąbar'];
        $response = new JsonResponse($arr);

        $this->assertEquals(
            json_encode($arr, JSON_UNESCAPED_UNICODE),
            $response->render($this->context)
        );
    }

    #[Test]
    public function it_throws_on_error(): void
    {
        $this->expectException(JsonException::class);

        $response = new JsonResponse(['hehe' => INF]);
        $response->render($this->context);
    }
}