<?php
declare(strict_types=1);

namespace Velo\Logger\Interfaces;

/**
 * Enforces format method implementation. It's a general Interface for all log formatters for Logger class.
 */
interface LogFormatter
{
    public function format(string $level, string $message, array $context = []): string;
}