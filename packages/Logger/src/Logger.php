<?php
declare(strict_types=1);

namespace Velo\Logger;

use Psr\Log\AbstractLogger;
use Stringable;
use InvalidArgumentException;
use Velo\Logger\Interfaces\LogFormatter;

/**
 * Basic Logger following Psr\Log\LoggerInterface.
 */
class Logger extends AbstractLogger
{
    public function __construct(
        protected string       $logFilePath,
        protected LogFormatter $logFormatter
    )
    {
    }

    /**
     * Logs a message with context at the given level.
     *
     * @param string $level Should be a value from Psr\Log\LogLevel or eventaully custom defined log level.
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!is_string($level) && !($level instanceof Stringable)) {
            throw new InvalidArgumentException('Level must be a string or an instance of Stringable!');
        }

        $formattedMessage = $this->logFormatter->format((string)$level, (string)$message, $context);

        $this->write($formattedMessage);
    }

    /**
     * Writes message to the log file, creates log directory if it doesn't exist.
     */
    protected function write(string $message): void
    {
        $dir = dirname($this->logFilePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->logFilePath, $message, FILE_APPEND | LOCK_EX);
    }
}