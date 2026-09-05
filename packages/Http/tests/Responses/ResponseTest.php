<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;
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
    public function it_gets_headers(): void
    {
        $response = $this->getResponseWithHeaders();

        self::assertEquals(['hehe' => 'hihi', 'a' => 'b', 'c' => 'D'], $response->getHeaders());
    }

    private function getResponseWithHeaders(): Response
    {
        return new class(200, ['hehe' => 'hihi', 'a' => 'b', 'C    ' => 'D']) extends Response {
            public function render(RenderContext $context): string
            {
                return '';
            }
        };
    }

    #[Test]
    public function it_gets_header(): void
    {
        $response = $this->getResponseWithHeaders();

        self::assertEquals('hihi', $response->getHeader('hehe'));
        self::assertEquals('b', $response->getHeader('A    '));
        self::assertEquals('D', $response->getHeader('c   '));
    }

    #[Test]
    public function it_sets_header(): void
    {
        self::assertEmpty($this->response->getHeaders());

        $this->response->setHeader('content-type', 'text/html');

        self::assertEquals(['content-type' => 'text/html'], $this->response->getHeaders());
    }

    #[Test]
    public function it_sets_headers(): void
    {
        self::assertEmpty($this->response->getHeaders());

        $this->response->setHeaders(['ConTEnt-type  ' => '  text/html', 'locaTioN' => '   hehe.com/Blog']);

        self::assertEquals(['content-type' => 'text/html', 'location' => 'hehe.com/Blog'], $this->response->getHeaders());
    }

    #[Test]
    public function it_sets_header_name_lowercase_and_trimmed_and_header_value_is_trimmed_but_not_lowercase(): void
    {
        self::assertEmpty($this->response->getHeaders());

        $this->response->setHeader(' Location   ', ' hehe.com/Blog');

        self::assertEquals(['location' => 'hehe.com/Blog'], $this->response->getHeaders());
    }

    #[Test]
    public function it_sets_header_if_the_header_does_not_exist(): void
    {
        $this->response->appendValueToHeader('LocaTion  ', ' hehe.com/Blog ');

        self::assertEquals(['location' => 'hehe.com/Blog'], $this->response->getHeaders());
    }

    #[Test]
    public function it_appends_value_to_existing_header(): void
    {
        $this->response->setHeader('Content-Type', 'text/html');

        $this->response->appendValueToHeader('Content-Type', 'charset=utf-8');

        self::assertEquals(['content-type' => 'text/html, charset=utf-8'], $this->response->getHeaders());
    }

    #[Test]
    public function it_does_not_append_value_if_it_already_exists(): void
    {
        $this->response->setHeader('AcCept  ', 'text/html, application/json   ');

        $this->response->appendValueToHeader('Accept', 'application/json');

        self::assertEquals(
            ['accept' => 'text/html, application/json'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_appends_value_if_it_is_only_a_part_of_an_existing_value(): void
    {
        $this->response->setHeader('Accept', 'application/json');

        $this->response->appendValueToHeader('Accept', 'text/html');

        self::assertEquals(
            ['accept' => 'application/json, text/html'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_does_not_append_value_if_it_is_the_first_value(): void
    {
        $this->response->setHeader('Accept', 'application/json, text/html');

        $this->response->appendValueToHeader('Accept', 'application/json');

        self::assertEquals(
            ['accept' => 'application/json, text/html'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_does_not_append_value_if_it_is_the_last_value(): void
    {
        $this->response->setHeader('Accept', 'application/json, text/html');

        $this->response->appendValueToHeader('Accept', 'text/html');

        self::assertEquals(
            ['accept' => 'application/json, text/html'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_does_not_append_value_if_it_is_in_the_middle(): void
    {
        $this->response->setHeader('Accept', 'application/json, text/html, text/plain');

        $this->response->appendValueToHeader('Accept', 'text/html');

        self::assertEquals(
            ['accept' => 'application/json, text/html, text/plain'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_handles_regex_special_characters_in_value(): void
    {
        $this->response->setHeader('Example', 'foo.bar, baz+qux');

        $this->response->appendValueToHeader('Example', 'foo.bar');

        $this->response->appendValueToHeader('Example', 'baz+qux');

        self::assertEquals(
            ['example' => 'foo.bar, baz+qux'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_appends_value_containing_regex_special_characters(): void
    {
        $this->response->setHeader('Example', 'foo');

        $this->response->appendValueToHeader('Example', 'bar.baz');

        self::assertEquals(
            ['example' => 'foo, bar.baz'],
            $this->response->getHeaders()
        );
    }

    #[Test]
    public function it_throws_when_header_is_not_string(): void
    {
        $this->expectException(Throwable::class);

        new class(headers: ['hehe' => 123]) extends Response
        {
            public function render(RenderContext $context): string
            {
                return '';
            }
        };
    }
}