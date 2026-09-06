<?php

declare(strict_types=1);

namespace Velo\Session\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Velo\Session\FlashMessages\FlashMessages;
use Velo\Session\Session\Interfaces\SessionInterface;

final class FlashMessagesTest extends TestCase
{
    private SessionInterface&MockObject $sessionMock;
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
            ->with('flash_messages', [FlashMessages::INFO => ['Test message']]);

        $self = $this->flashMessages->add(FlashMessages::INFO, 'Test message');

        self::assertSame($this->flashMessages, $self);
    }

    #[Test]
    public function it_gets_messages_for_specific_type_and_removes_them_from_session(): void
    {
        $existingMessages = [
            FlashMessages::SUCCESS => ['Operation successful'],
            FlashMessages::ERROR => ['An error occurred'],
        ];

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($existingMessages);

        $this->sessionMock->expects($this->once())
            ->method('set')
            ->with('flash_messages', [FlashMessages::ERROR => ['An error occurred']]);

        $result = $this->flashMessages->get(FlashMessages::SUCCESS);

        self::assertSame(['Operation successful'], $result);
    }

    #[Test]
    public function it_removes_entire_session_key_when_last_flash_message_is_retrieved(): void
    {
        $existingMessages = [
            FlashMessages::SUCCESS => ['Operation successful'],
        ];

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($existingMessages);

        $this->sessionMock->expects($this->once())
            ->method('remove')
            ->with('flash_messages');

        $result = $this->flashMessages->get(FlashMessages::SUCCESS);

        self::assertSame(['Operation successful'], $result);
    }

    #[Test]
    public function it_returns_default_when_type_does_not_exist(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn([]);

        $result = $this->flashMessages->get(FlashMessages::WARNING, ['default_message']);

        self::assertSame(['default_message'], $result);
    }

    #[Test]
    public function it_gets_all_messages_and_cleans_session(): void
    {
        $existingMessages = [
            FlashMessages::SUCCESS => ['Message 1'],
            FlashMessages::INFO => ['Message 2'],
        ];

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($existingMessages);

        $this->sessionMock->expects($this->once())
            ->method('remove')
            ->with('flash_messages');

        $result = $this->flashMessages->getAll();

        self::assertSame($existingMessages, $result);
    }

    /**
     * @param array<string, list<string>> $storedMessages
     */
    #[Test]
    #[DataProvider('hasTypeDataProvider')]
    public function it_checks_if_type_exists(array $storedMessages, string $checkType, bool $expected): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('flash_messages', [])
            ->willReturn($storedMessages);

        self::assertSame($expected, $this->flashMessages->has($checkType));
    }

    /**
     * @return array<string, array{0: array<string, list<string>>, 1: string, 2: bool}>
     */
    public static function hasTypeDataProvider(): array
    {
        return [
            'has_success' => [[FlashMessages::SUCCESS => ['OK']], FlashMessages::SUCCESS, true],
            'does_not_have_error' => [[FlashMessages::SUCCESS => ['OK']], FlashMessages::ERROR, false],
            'does_not_have_info' => [[], FlashMessages::INFO, false],
        ];
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

        self::assertSame($this->flashMessages, $self);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function helperMethodsDataProvider(): array
    {
        return [
            'success_msgs' => ['success', FlashMessages::SUCCESS],
            'error_msgs' => ['error', FlashMessages::ERROR],
            'warning_msgs' => ['warning', FlashMessages::WARNING],
            'info_msgs' => ['info', FlashMessages::INFO],
        ];
    }
}