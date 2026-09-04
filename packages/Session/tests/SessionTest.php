<?php
declare(strict_types=1);

namespace Velo\Session\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Session\Session\Session;

final class SessionTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->session = new Session();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function it_sets_value_in_session_and_returns_self(): void
    {
        $self = $this->session->set('user_id', 123);

        self::assertSame(123, $_SESSION['user_id'] ?? null);
        self::assertSame($this->session, $self);
    }

    #[Test]
    public function it_gets_value_from_session(): void
    {
        $_SESSION['theme'] = 'dark';

        self::assertSame('dark', $this->session->get('theme'));
    }

    #[Test]
    public function it_returns_default_value_when_key_does_not_exist(): void
    {
        self::assertNull($this->session->get('non_existing'));
        self::assertSame('default_val', $this->session->get('non_existing', 'default_val'));
    }

    #[Test]
    #[DataProvider('hasKeyDataProvider')]
    public function it_checks_if_key_exists_in_session(string $key, bool $expected): void
    {
        $_SESSION['existing_key'] = 'value';

        self::assertSame($expected, $this->session->has($key));
    }

    #[Test]
    public function it_removes_key_from_session_and_returns_self(): void
    {
        $_SESSION['to_remove'] = 'data';

        $self = $this->session->remove('to_remove');

        self::assertArrayNotHasKey('to_remove', $_SESSION);
        self::assertSame($this->session, $self);
    }

    #[Test]
    public function it_sets_flash_data_and_returns_self(): void
    {
        $self = $this->session->setFlash('old_input', ['name' => 'John']);

        self::assertSame(['name' => 'John'], $_SESSION['flash_data']['old_input'] ?? null);
        self::assertSame($this->session, $self);
    }

    #[Test]
    public function it_gets_flash_data_and_cleans_it_up(): void
    {
        $this->session->setFlash('errors', ['email' => 'Invalid email']);

        self::assertTrue($this->session->hasFlash('errors'));
        self::assertSame(['email' => 'Invalid email'], $this->session->getFlash('errors'));
        self::assertFalse($this->session->hasFlash('errors'));
        self::assertArrayNotHasKey('flash_data', $_SESSION);
    }

    #[Test]
    public function it_returns_default_when_flash_key_does_not_exist(): void
    {
        self::assertSame([], $this->session->getFlash('missing', []));
    }

    #[Test]
    public function it_retruns_false_if_no_flash(): void
    {
        self::assertFalse($this->session->hasFlash('sth'));
    }

    public static function hasKeyDataProvider(): array
    {
        return [
            ['existing_key', true],
            ['missing_key', false],
        ];
    }
}