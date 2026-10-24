<?php
declare(strict_types=1);

namespace Velo\Middlewares\AntiCsrf;

interface AntiCsrfTokenManagerInterface
{
    public function getToken(): string;

    /**
     * @return string New token
     */
    public function regenerateToken(): string;

    public function isValid(mixed $candidate): bool;
}