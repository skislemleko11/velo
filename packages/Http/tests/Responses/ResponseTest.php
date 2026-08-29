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
        $response = new FakeResponse();

        $this->assertEmpty($response->headers);

        $response->setHeader('Content-Type', 'text/html');

        $this->assertEquals(['Content-Type' => 'text/html'], $response->headers);
    }

    #[Test]
    public function it_appends_value_to_existing_header(): void
    {
        $response = new FakeResponse();

        $response->setHeader('Content-Type', 'text/html');

        $response->appendValueToHeader('Content-Type', 'charset=utf-8');

        $this->assertEquals(['Content-Type' => 'text/html, charset=utf-8'], $response->headers);
    }

    #[Test]
    public function it_sets_header_if_the_header_does_not_exist(): void
    {
        $response = new FakeResponse();

        $response->appendValueToHeader('Content-Type', 'text/html');

        $this->assertEquals(['Content-Type' => 'text/html'], $response->headers);
    }
}

class FakeResponse extends Response {
    public function render(RenderContext $context): string
    {
        return '';
    }
}