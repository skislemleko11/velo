<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\ResponseRenderer;
use Velo\Http\HttpResponse;

class ResponseRendererTest extends TestCase
{
    #[Test]
    public function it_echos_json_response_sets_headers_and_response_code(): void
    {
        $response = new HttpResponse(null, 300, ['test' => 'correct']);

        $renderer = $this->getMockBuilder(ResponseRenderer::class)
            ->onlyMethods(['terminate', 'setHeader', 'setStatusCode'])
            ->getMock();

        $renderer->expects(self::once())
            ->method('terminate');
        $renderer->expects(self::once())
            ->method('setHeader')
            ->with('Content-Type: application/json');
        $renderer->expects(self::once())
            ->method('setStatusCode')
            ->with(300);

        $this->expectOutputString(json_encode(['test' => 'correct']));

        $renderer->render($response);
    }

    #[Test]
    public function it_renders_view_extracts_variables_and_sets_response_code(): void
    {
        $filename = 'ResponseRendererTest_testfile.txt';
        $response = new HttpResponse($filename, 200, ['test' => 'correct']);

        $renderer = $this->getMockBuilder(ResponseRenderer::class)
            ->onlyMethods(['terminate', 'setStatusCode'])
            ->getMock();


        $renderer->expects(self::once())
            ->method('terminate');
        $renderer->expects(self::once())
            ->method('setStatusCode')
            ->with(200);
        $this->expectOutputString('hehe');

        file_put_contents($filename, 'hehe');

        $renderer->render($response);

        unlink($filename);
    }
}