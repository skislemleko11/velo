<?php
declare(strict_types=1);

namespace Velo\Logger;

use DateTimeImmutable;
use Stringable;
use Throwable;
use Velo\Logger\Interfaces\LogFormatter;

/**
 * Basic text log formatter for Logger Class.
 *
 * You can extend it to change FORMAT or THROWABLE_FORMAT consts.
 */
class LogTextFormatter implements LogFormatter
{
    protected const string FORMAT = "[%datetime%] [%level%] %message%\n%context%\n";
    protected const string THROWABLE_FORMAT = "--- Stack Trace: %s: %s in %s:%d\n%s";

    /**
     * Formats a log message with the given level, message, and context.
     *
     * @param string $level Should be a value from Psr\Log\LogLevel or eventually custom defined log level.
     */
    public function format(string $level, string $message, array $context = []): string
    {
        $datetime = new DateTimeImmutable()->format('Y-m-d H:i:s.v');
        $exceptionString = '';

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exceptionString = $this->formatThrowable($context['exception']);
            unset($context['exception']);
        }

        $messageString = $this->interpolate($message, $context);

        $contextString = $context
            ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: ''
            : '';

        $output = strtr(self::FORMAT, [
            '%datetime%' => $datetime,
            '%level%' => strtoupper($level),
            '%message%' => $messageString,
            '%context%' => $contextString,
        ]);

        if ($exceptionString !== '') {
            $output .= $exceptionString . "\n";
        }

        return $output . "\n";
    }

    /**
     * Replaces placeholders in the message with context values.
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || $val instanceof Stringable)) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Formats Throwable to a string using the THROWABLE_FORMAT const.
     */
    private function formatThrowable(Throwable $exception): string
    {
        return sprintf(
            self::THROWABLE_FORMAT,
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }
}