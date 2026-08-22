<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses\Concrete;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\RedirectResponse;

final class RedirectResponseTest extends TestCase
{
    #[Test]
    public function it_sets_location_header(): void
    {
        $response = new RedirectResponse('a', headers: ['Location' => 'b', 'hihi' => 'haha']);
        $this->assertEquals(['Location' => 'a', 'hihi' => 'haha'], $response->headers);
    }

    #[Test]
    public function it_returns_empty_string(): void
    {
        $response = new RedirectResponse('a');
        $this->assertEquals('', $response->render($this->createStub(RenderContext::class)));
    }
}