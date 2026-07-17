<?php
declare(strict_types=1);

namespace Velo\Logger\Interfaces;

interface LogFormatter
{
    public function format(string $level, string $message, array $context = []): string;
}