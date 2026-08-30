<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Response;

final class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new class() extends Response {
            public function render(RenderContext $context): string
            {
                return '';
            }
        };
    }

    #[Test]
    public function it_sets_header(): void
    {
        $this->assertEmpty($this->response->headers);

        $this->response->setHeader('Content-Type', 'text/html');

        $this->assertEquals(['Content-Type' => 'text/html'], $this->response->headers);
    }

    #[Test]
    public function it_sets_header_if_the_header_does_not_exist(): void
    {
        $this->response->appendValueToHeader('Content-Type', 'text/html');

        $this->assertEquals(['Content-Type' => 'text/html'], $this->response->headers);
    }

    #[Test]
    public function it_appends_value_to_existing_header(): void
    {
        $this->response->setHeader('Content-Type', 'text/html');

        $this->response->appendValueToHeader('Content-Type', 'charset=utf-8');

        $this->assertEquals(['Content-Type' => 'text/html, charset=utf-8'], $this->response->headers);
    }

    #[Test]
    public function it_does_not_append_value_if_it_already_exists(): void
    {
        $this->response->setHeader('Accept', 'text/html, application/json');

        $this->response->appendValueToHeader('Accept', 'application/json');

        $this->assertEquals(
            ['Accept' => 'text/html, application/json'],
            $this->response->headers
        );
    }

    #[Test]
    public function it_appends_value_if_it_is_only_a_part_of_an_existing_value(): void
    {
        $this->response->setHeader('Accept', 'application/json');

        $this->response->appendValueToHeader('Accept', 'text/html');

        $this->assertEquals(
            ['Accept' => 'application/json, text/html'],
            $this->response->headers
        );
    }

    #[Test]
    public function it_does_not_append_value_if_it_is_the_first_value(): void
    {
        $this->response->setHeader('Accept', 'application/json, text/html');

        $this->response->appendValueToHeader('Accept', 'application/json');

        $this->assertEquals(
            ['Accept' => 'application/json, text/html'],
            $this->response->headers
        );
    }

    #[Test]
    public function it_does_not_append_value_if_it_is_the_last_value(): void
    {
        $this->response->setHeader('Accept', 'application/json, text/html');

        $this->response->appendValueToHeader('Accept', 'text/html');

        $this->assertEquals(
            ['Accept' => 'application/json, text/html'],
            $this->response->headers
        );
    }

    #[Test]
    public function it_does_not_append_value_if_it_is_in_the_middle(): void
    {
        $this->response->setHeader('Accept', 'application/json, text/html, text/plain');

        $this->response->appendValueToHeader('Accept', 'text/html');

        $this->assertEquals(
            ['Accept' => 'application/json, text/html, text/plain'],
            $this->response->headers
        );
    }

    #[Test]
    public function it_handles_regex_special_characters_in_value(): void
    {
        $this->response->setHeader('Example', 'foo.bar, baz+qux');

        $this->response->appendValueToHeader('Example', 'foo.bar');

        $this->response->appendValueToHeader('Example', 'baz+qux');

        $this->assertEquals(
            ['Example' => 'foo.bar, baz+qux'],
            $this->response->headers
        );
    }

    #[Test]
    public function it_appends_value_containing_regex_special_characters(): void
    {
        $this->response->setHeader('Example', 'foo');

        $this->response->appendValueToHeader('Example', 'bar.baz');

        $this->assertEquals(
            ['Example' => 'foo, bar.baz'],
            $this->response->headers
        );
    }
}