<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\Emitter\Interfaces\EmitterInterface;
use Velo\Http\RenderContext;
use Velo\Http\RequestMethod;
use Velo\Http\ResponseRenderer;
use Velo\Http\Responses\Concrete\TextResponse;

#[AllowMockObjectsWithoutExpectations]
final class ResponseRendererTest extends TestCase
{
    #[Test]
    public function it_renders_response_and_emits_it(): void
    {
        $response = new TextResponse(
            'Hello world',
            201,
            ['Content-Type' => 'text/plain']
        );

        $emitter = $this->createMock(EmitterInterface::class);
        $context = $this->createMock(RenderContext::class);

        $emitter
            ->expects(self::once())
            ->method('setStatusCode')
            ->with(201)
            ->willReturnSelf();

        $emitter
            ->expects(self::once())
            ->method('sendHeaders')
            ->with([
                'Content-Type' => 'text/plain',
                'Content-Length' => '11',
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Terminate');

        $emitter
            ->expects(self::once())
            ->method('terminate')
            ->willThrowException(new Exception('Terminate'));

        $renderer = new ResponseRenderer($emitter, $context);

        ob_start();

        try {
            $renderer->render($response);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('Hello world', $output);
    }

    #[Test]
    public function it_does_not_render_content_for_head_request(): void
    {
        $response = new TextResponse(
            'Hello world',
            200,
            ['Content-Type' => 'text/plain']
        );

        $emitter = $this->createMock(EmitterInterface::class);
        $context = $this->createMock(RenderContext::class);

        $emitter
            ->expects(self::once())
            ->method('setStatusCode')
            ->with(200)
            ->willReturnSelf();

        $emitter
            ->expects(self::once())
            ->method('sendHeaders')
            ->with([
                'Content-Type' => 'text/plain',
                'Content-Length' => '11',
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Terminate');

        $emitter
            ->expects(self::once())
            ->method('terminate')
            ->willThrowException(new Exception('Terminate'));

        $renderer = new ResponseRenderer($emitter, $context);

        ob_start();

        try {
            $renderer->render($response, RequestMethod::HEAD);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('', $output);
    }

    #[Test]
    public function it_does_not_override_existing_content_length(): void
    {
        $response = new TextResponse(
            'Hello world',
            headers: [
                'Content-Length' => '999',
            ]
        );

        $emitter = $this->createMock(EmitterInterface::class);
        $context = $this->createMock(RenderContext::class);

        $emitter
            ->expects(self::once())
            ->method('setStatusCode')
            ->with(200)
            ->willReturnSelf();

        $emitter
            ->expects(self::once())
            ->method('sendHeaders')
            ->with([
                'Content-Length' => '999',
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Terminate');

        $emitter
            ->expects(self::once())
            ->method('terminate')
            ->willThrowException(new Exception('Terminate'));

        $renderer = new ResponseRenderer($emitter, $context);

        ob_start();

        try {
            $renderer->render($response);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('Hello world', $output);
    }
}