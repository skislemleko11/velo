<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses\Concrete;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\NoContentResponse;

final class NoContentResponseTest extends TestCase
{
    #[Test]
    public function it_renders_empty_string(): void
    {
        $response = new NoContentResponse();
        $context = self::createStub(RenderContext::class);

        self::assertSame('', $response->render($context));
    }

    #[Test]
    public function it_always_sets_content_length_header_to_0(): void
    {
        $response = new NoContentResponse(headers: ['Content-Length' => '30']);

        self::assertSame('0', $response->getHeader('Content-Length'));
    }
}