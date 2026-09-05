<?php
declare(strict_types=1);

namespace Velo\Logger\Interfaces;

/**
 * Enforces format method implementation. It's a general Interface for all log formatters for Logger class.
 */
interface LogFormatter
{
    /**
     * Formats a log message with the given level, message, and context.
     *
     * @param string $level Should be a value from Psr\Log\LogLevel or eventually custom defined log level.
     * @param array<string, mixed> $context
     */
    public function format(string $level, string $message, array $context = []): string;
}