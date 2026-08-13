<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\Emitter\NativeEmitter;

class NativeEmitterTest extends TestCase
{
    private NativeEmitter $emitter;

    protected function setUp(): void
    {
        $this->emitter = new NativeEmitter();
    }

    #[Test]
    public function it_sets_status_code(): void
    {
        http_response_code(200);

        $result = $this->emitter->setStatusCode(404);

        $this->assertSame($this->emitter, $result);
        $this->assertSame(404, http_response_code());
    }

    #[Test]
    public function it_sends_header(): void
    {
        $resultSingle = $this->emitter->sendHeader('X-Test-Single', 'Value1');

        $this->assertSame($this->emitter, $resultSingle);

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertContains('X-Test-Single: Value1', $headers);
        } else {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function it_sends_headers(): void
    {
        $resultMultiple = $this->emitter->sendHeaders([
            'X-Test-Multi-1' => 'Value2',
            'X-Test-Multi-2' => 'Value3',
        ]);

        $this->assertSame($this->emitter, $resultMultiple);

        if (function_exists('xdebug_get_headers')) {
            $headers = xdebug_get_headers();
            $this->assertContains('X-Test-Multi-1: Value2', $headers);
            $this->assertContains('X-Test-Multi-2: Value3', $headers);
        } else {
            $this->assertTrue(true);
        }
    }
}