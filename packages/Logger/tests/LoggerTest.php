<?php
declare(strict_types=1);

namespace Velo\Logger\Tests;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use stdClass;
use Velo\Logger\Interfaces\LogFormatter;
use Velo\Logger\Logger;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
final class LoggerTest extends TestCase
{
    private LogFormatter&MockObject $logFormatterMock;
    private string $logPath;

    protected function setUp(): void
    {
        $this->logFormatterMock = $this->createMock(LogFormatter::class);
        $this->logPath = sys_get_temp_dir() . '/velo_logger_test_' . uniqid('', true) . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    #[Test]
    public function it_takes_string_log_level(): void
    {
        $logger = new Logger($this->logPath, $this->logFormatterMock);

        $logger->log('a', 'Test message');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function it_takes_stringable_log_level(): void
    {
        $logger = new Logger($this->logPath, $this->logFormatterMock);

        $logger->log(new Exception('hehe'), 'Test message');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    #[DataProvider('invalidLogLevelsTestCases')]
    public function it_throws_exception_when_log_level_is_invalid(mixed $val): void
    {
        $logger = new Logger($this->logPath, $this->logFormatterMock);

        $this->expectException(InvalidArgumentException::class);

        $logger->log($val, 'Test message');
    }

    /**
     * @return array<string, mixed>
     */
    public static function invalidLogLevelsTestCases(): array
    {
        return [
            'Closure' => [fn() => 'hehe'],
            'int' => [1],
            'float' => [1.5],
            'array' => [['a']],
            'object' => [new stdClass()]
        ];
    }

    #[Test]
    public function it_formats_and_writes_log_message(): void
    {
        $logger = new Logger($this->logPath, $this->logFormatterMock);

        $this->logFormatterMock->expects($this->once())
            ->method('format')
            ->with('info', 'Test message', [])
            ->willReturn('Formatted message');

        $logger->log('info', 'Test message');

        self::assertStringEqualsFile($this->logPath, 'Formatted message');
    }

    #[Test]
    #[DataProvider('invokesMethodCases')]
    public function it_executes_methods_correctly(string $methodName, string $logLevel): void
    {
        $message = 'Test message';
        $context = ['hehe'];

        $this->logFormatterMock->expects($this->once())
            ->method('format')
            ->with($logLevel, $message, $context)
            ->willReturn('Formatted message');

        $logger = new Logger($this->logPath, $this->logFormatterMock);
        $logger->$methodName($message, $context);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invokesMethodCases(): array
    {
        return [
            'emergency' => ['emergency', LogLevel::EMERGENCY],
            'alert' => ['alert', LogLevel::ALERT],
            'critical' => ['critical', LogLevel::CRITICAL],
            'error' => ['error', LogLevel::ERROR],
            'warning' => ['warning', LogLevel::WARNING],
            'notice' => ['notice', LogLevel::NOTICE],
            'info' => ['info', LogLevel::INFO],
            'debug' => ['debug', LogLevel::DEBUG]
        ];
    }
}