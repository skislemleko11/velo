<?php
declare(strict_types=1);

namespace Velo\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Core\Config;

class ConfigTest extends TestCase
{
    #[Test]
    public function it_creates_and_gets_key(): void
    {
        $config = new Config(['key' => 'value']);
        $this->assertSame('value', $config->get('key'));
    }

    #[Test]
    public function it_creates_and_gets_default_value(): void
    {
        $config = new Config(['key' => 'value']);
        $this->assertSame('default', $config->get('nonexistent', 'default'));
    }
}