<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses\Concrete;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\ViewResponse;
use Velo\View\ViewRenderer;

final class ViewResponseTest extends TestCase
{
    #[Test]
    public function it_sets_content_type_to_html(): void
    {
        $response = new ViewResponse('view', headers: ['Content-Type' => 'bubu', 'h' => 'a']);

        $this->assertEquals(['Content-Type' => 'text/html; charset=utf-8', 'h' => 'a'], $response->headers);
    }

    #[Test]
    public function it_uses_view_renderer_render_method_to_render(): void
    {
        $response = new ViewResponse('view', ['key' => 'value']);

        $viewRenderer = $this->createMock(ViewRenderer::class);

        $viewRenderer
            ->expects(self::once())
            ->method('render')
            ->with('view', ['key' => 'value']);

        $context = new RenderContext($viewRenderer);

        $response->render($context);
    }
}