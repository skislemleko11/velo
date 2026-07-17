<?php
declare(strict_types=1);

namespace Velo\Logger;

use Throwable;
use Velo\Logger\Interfaces\LogFormatter;

class LogTextFormatter implements LogFormatter
{
    protected string $format = "[%datetime%] [%level%] %message% %context%\n\n";

    public function format(string $level, string $message, array $context = []): string
    {
        $datetime = date('Y-m-d H:i:s');
        $exceptionString = '';

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exceptionString = $this->formatThrowable($context['exception']);
            unset($context['exception']);
        }

        $messageString = $this->interpolate($message, $context);

        $contextString = !empty($context)
            ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $output = strtr($this->format, [
            '%datetime%' => $datetime,
            '%level%' => strtoupper($level),
            '%message%' => $messageString,
            '%context%' => $contextString,
        ]);

        if ($exceptionString != '')
            $output .= $exceptionString . "\n";

        return $output;
    }

    protected function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $val) {
            if (!is_array($val) || (!is_object($val) || method_exists($val, '__toString')))
                $replace['{' . $key . '}'] = (string)$val;
        }

        return strtr($message, $replace);
    }

    protected function formatThrowable(Throwable $exception): string
    {
        return sprintf(
            "--- Stack Trace: %s: %s in %s:%d\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }
}