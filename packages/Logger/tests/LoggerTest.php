<?php
declare(strict_types=1);

namespace Velo\Logger\Tests;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use stdClass;
use Velo\Logger\Interfaces\LogFormatter;
use Velo\Logger\Logger;
use Velo\Logger\LogTextFormatter;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class LoggerTest extends TestCase
{
    protected Logger $logger;
    protected LogFormatter $logFormatter;
    protected const string LOG_PATH = 'testfile.log';

    protected function setUp(): void
    {
        $this->logFormatter = $this->createMock(LogTextFormatter::class);
        $this->logger = $this->getMockBuilder(Logger::class)
            ->setConstructorArgs([self::LOG_PATH, $this->logFormatter])
            ->onlyMethods(['write'])
            ->getMock();
    }

    #[Test]
    public function it_takes_string_log_level(): void
    {
        $this->logger->log('a', 'Test message');
        $this->assertTrue(true);
    }

    #[Test]
    public function it_takes_stringable_log_level(): void
    {
        $this->logger->log(new Exception('hehe'), 'Test message');
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProvider('invalidLogLevelsTestCases')]
    public function it_throws_excetion_when_log_level_is_invalid($val): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->log($val, 'Test message');
    }

    public static function invalidLogLevelsTestCases(): array
    {
        return [
            [fn() => 'hehe'],
            [1],
            [1.5],
            [['a']],
            [new stdClass()]
        ];
    }

    #[Test]
    public function it_formats_log_message(): void
    {
        $this->logFormatter->expects($this->once())
            ->method('format')
            ->with('info', 'Test message', [])
            ->willReturn('Formatted message');

        $this->logger->log('info', 'Test message');
    }

    #[Test]
    public function it_writes_log_message(): void
    {
        $this->logger->expects($this->once())
            ->method('write');

        $this->logger->log('info', 'Test message');
    }

    #[Test]
    #[DataProvider('invokesMethodCases')]
    public function it_executes_methods_correctly($methodName, $logLevel): void
    {
        $message = 'Test message';
        $context = ['hehe'];

        $logger = $this->getMockBuilder(Logger::class)
            ->setConstructorArgs([self::LOG_PATH, $this->logFormatter])
            ->onlyMethods(['log'])
            ->getMock();

        $logger->expects($this->once())
            ->method('log')
            ->with($logLevel, $message, $context);

        $logger->$methodName($message, $context);
    }

    public static function invokesMethodCases(): array
    {
        return [
            ['emergency', LogLevel::EMERGENCY],
            ['alert', LogLevel::ALERT],
            ['critical', LogLevel::CRITICAL],
            ['error', LogLevel::ERROR],
            ['warning', LogLevel::WARNING],
            ['notice', LogLevel::NOTICE],
            ['info', LogLevel::INFO],
            ['debug', LogLevel::DEBUG]
        ];
    }
}