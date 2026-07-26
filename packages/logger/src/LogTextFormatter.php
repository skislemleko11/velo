<?php
declare(strict_types=1);

namespace Velo\Logger;

use DateTimeImmutable;
use Throwable;
use Velo\Logger\Interfaces\LogFormatter;

/**
 * Basic text log formatter for Logger Class.
 */
class LogTextFormatter implements LogFormatter
{
    protected const string FORMAT = "[%datetime%] [%level%] %message%\n%context%\n";
    protected const string THROWABLE_FORMAT = "--- Stack Trace: %s: %s in %s:%d\n%s";

    /**
     * Formats a log message with the given level, message, and context.
     *
     * @param string $level should be a value from Psr\Log\LogLevel or eventaully custom defined log level.
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

        $contextString = !empty($context)
            ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
            : '';

        $output = strtr(self::FORMAT, [
            '%datetime%' => $datetime,
            '%level%' => strtoupper($level),
            '%message%' => $messageString,
            '%context%' => $contextString,
        ]);

        if ($exceptionString != '') {
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
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Universally formats Throwable.
     */
    private function formatThrowable(Throwable $exception): string
    {
        return sprintf(
            self::THROWABLE_FORMAT,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }
}