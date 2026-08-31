<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\Cors;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Middlewares\Cors\CorsConfig\CorsConfig;
use Velo\Middlewares\Cors\CorsConfig\Exceptions\InvalidConfigurationException;

final class CorsConfigTest extends TestCase
{
    #[Test]
    public function it_sets_allow_all_origins_if_star_is_included_in_allowed_origins(): void
    {
        $config = new CorsConfig(
            ['hehe', 'haha', 'hihi', '*']
        );

        self::assertTrue($config->allowAllOrigins);
    }

    #[Test]
    public function it_makes_both_allowed_and_exposed_headers_lowercase(): void
    {
        $config = new CorsConfig(
            allowedHeaders: ['Content-Type', 'Content-Length'],
            exposedHeaders: ['Content-Type', 'Content-Length']
        );

        self::assertEquals(['content-type', 'content-length'], $config->allowedHeaders);
        self::assertEquals(['content-type', 'content-length'], $config->allowedHeaders);
    }

    #[Test]
    public function it_sets_max_age_seconds_to_zero_if_negative(): void
    {
        $config = new CorsConfig(
            ['hehe', 'haha', 'hihi'],
            maxAgeSeconds: -1
        );

        self::assertEquals(0, $config->maxAgeSeconds);
    }

    #[Test]
    public function it_sets_max_age_seconds_to_provided_value_when_positive(): void
    {
        $config = new CorsConfig(
            ['hehe', 'haha', 'hihi'],
            maxAgeSeconds: 10
        );

        self::assertEquals(10, $config->maxAgeSeconds);
    }

    #[Test]
    public function it_throws_exception_if_allows_all_origins_and_credentials(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new CorsConfig(['hehe', 'haha', 'hihi', '*'], allowCredentials: true);
    }
}