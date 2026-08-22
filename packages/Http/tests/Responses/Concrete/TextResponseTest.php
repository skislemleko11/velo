<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses\Concrete;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\TextResponse;

final class TextResponseTest extends TestCase
{
    #[Test]
    public function it_sets_content_type_to_text_plain_if_not_set(): void
    {
        $response = new TextResponse('hehe', headers: ['h' => 'a']);

        $this->assertEquals(['Content-Type' => 'text/plain; charset=utf-8', 'h' => 'a'], $response->headers);
    }

    #[Test]
    public function it_leaves_content_type_if_set(): void
    {
        $response = new TextResponse('color :3', headers: ['Content-Type' => 'text/css']);

        $this->assertEquals(['Content-Type' => 'text/css'], $response->headers);
    }

    #[Test]
    public function it_returns_content(): void
    {
        $response = new TextResponse('hehe');

        $this->assertEquals(
            'hehe',
            $response->render($this->createStub(RenderContext::class))
        );
    }
}