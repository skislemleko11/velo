<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Response;

final class ResponseTest extends TestCase
{
    #[Test]
    public function it_sets_header(): void
    {
        $response = new class() extends Response {
            public function render(RenderContext $context): string
            {
                return 'hehe';
            }
        };

        $this->assertEmpty($response->headers);

        $response->setHeader('Content-Type', 'text/html');

        $this->assertEquals(['Content-Type' => 'text/html'], $response->headers);
    }
}
