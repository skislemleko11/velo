<?php

declare(strict_types=1);

namespace Velo\Session\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Session\FlashMessages\FlashMessages;
use Velo\Session\Session\Interfaces\SessionInterface;

class FlashMessagesTest extends TestCase
{
    private SessionInterface $sessionMock;
    private FlashMessages $flashMessages;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $this->flashMessages = new FlashMessages($this->sessionMock);
    }

    #[Test]
    public function it_adds_message_and_returns_self(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn([]);

        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with('flash_messages', ['info' => ['Test message']]);

        $self = $this->flashMessages->add('info', 'Test message');

        $this->assertSame($this->flashMessages, $self);
    }

    #[Test]
    public function it_gets_messages_for_specific_type_and_removes_them_from_session(): void
    {
        $existingMessages = [
            'success' => ['Operation successful'],
            'error'   => ['An error occurred'],
        ];

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($existingMessages);

        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with('flash_messages', ['error' => ['An error occurred']]);

        $result = $this->flashMessages->get('success');

        $this->assertSame(['Operation successful'], $result);
    }

    #[Test]
    public function it_removes_entire_session_key_when_last_flash_message_is_retrieved(): void
    {
        $existingMessages = [
            'success' => ['Operation successful'],
        ];

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($existingMessages);

        $this->sessionMock->expects($this->once())
            ->method('remove')
            ->with('flash_messages');

        $result = $this->flashMessages->get('success');

        $this->assertSame(['Operation successful'], $result);
    }

    #[Test]
    public function it_returns_default_when_type_does_not_exist(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn([]);

        $result = $this->flashMessages->get('warning', ['default_message']);

        $this->assertSame(['default_message'], $result);
    }

    #[Test]
    public function it_gets_all_messages_and_cleans_session(): void
    {
        $existingMessages = [
            'success' => ['Message 1'],
            'info'    => ['Message 2'],
        ];

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($existingMessages);

        $this->sessionMock->expects($this->once())
            ->method('remove')
            ->with('flash_messages');

        $result = $this->flashMessages->getAll();

        $this->assertSame($existingMessages, $result);
    }

    #[Test]
    #[DataProvider('hasTypeDataProvider')]
    public function it_checks_if_type_exists(array $storedMessages, string $checkType, bool $expected): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($storedMessages);

        $this->assertSame($expected, $this->flashMessages->has($checkType));
    }

    #[Test]
    #[DataProvider('helperMethodsDataProvider')]
    public function it_triggers_helper_methods_and_returns_self(string $method, string $type): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn([]);

        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with('flash_messages', [$type => ['Helper message']]);

        $self = $this->flashMessages->$method('Helper message');

        $this->assertSame($this->flashMessages, $self);
    }

    public static function hasTypeDataProvider(): array
    {
        return [
            [['success' => ['OK']], 'success', true],
            [['success' => ['OK']], 'error', false],
            [[], 'info', false],
        ];
    }

    public static function helperMethodsDataProvider(): array
    {
        return [
            ['success', 'success'],
            ['error', 'error'],
            ['warning', 'warning'],
            ['info', 'info'],
        ];
    }
}