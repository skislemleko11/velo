<?php
declare(strict_types=1);

namespace Velo\Core\ThrowableHandling\ErrorResponseFormatter\Interfaces;

use Velo\Http\HttpResponse;
use Throwable;

interface ErrorResponseFormatterInterface
{
    public function formatJson(Throwable $throwable): HttpResponse;

    public function formatPlainText(Throwable $throwable): HttpResponse;

    public function formatView(Throwable $throwable): HttpResponse;
}