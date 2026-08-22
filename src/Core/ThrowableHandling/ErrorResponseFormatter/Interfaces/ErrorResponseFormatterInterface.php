<?php
declare(strict_types=1);

namespace Velo\Core\ThrowableHandling\ErrorResponseFormatter\Interfaces;

use Velo\Http\Responses\Concrete\JsonResponse;
use Velo\Http\Responses\Concrete\TextResponse;
use Velo\Http\Responses\Concrete\ViewResponse;
use Throwable;

interface ErrorResponseFormatterInterface
{
    public function formatJson(Throwable $throwable): JsonResponse;

    public function formatPlainText(Throwable $throwable): TextResponse;

    public function formatView(Throwable $throwable): ViewResponse|TextResponse;
}