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
use Velo\Logger\LogTextFormatter;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
final class LoggerTest extends TestCase
{
    private Logger&MockObject $loggerMock;
    private LogFormatter&MockObject $logFormatterMock;
    private const string LOG_PATH = 'testfile.log';

    protected function setUp(): void
    {
        $this->logFormatterMock = $this->createMock(LogTextFormatter::class);
        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->setConstructorArgs([self::LOG_PATH, $this->logFormatterMock])
            ->onlyMethods(['write'])
            ->getMock();
    }

    #[Test]
    public function it_takes_string_log_level(): void
    {
        $this->loggerMock->log('a', 'Test message');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function it_takes_stringable_log_level(): void
    {
        $this->loggerMock->log(new Exception('hehe'), 'Test message');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    #[DataProvider('invalidLogLevelsTestCases')]
    public function it_throws_excetion_when_log_level_is_invalid(mixed $val): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loggerMock->log($val, 'Test message');
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
    public function it_formats_log_message(): void
    {
        $this->logFormatterMock->expects($this->once())
            ->method('format')
            ->with('info', 'Test message', [])
            ->willReturn('Formatted message');

        $this->loggerMock->log('info', 'Test message');
    }

    #[Test]
    public function it_writes_log_message(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('write');

        $this->loggerMock->log('info', 'Test message');
    }

    #[Test]
    #[DataProvider('invokesMethodCases')]
    public function it_executes_methods_correctly(string $methodName, string $logLevel): void
    {
        $message = 'Test message';
        $context = ['hehe'];

        $logger = $this->getMockBuilder(Logger::class)
            ->setConstructorArgs([self::LOG_PATH, $this->logFormatterMock])
            ->onlyMethods(['log'])
            ->getMock();

        $logger->expects($this->once())
            ->method('log')
            ->with($logLevel, $message, $context);

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