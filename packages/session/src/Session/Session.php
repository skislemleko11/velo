<?php
declare(strict_types=1);

namespace Velo\Session\Session;

use Velo\Session\Session\Interfaces\SessionInterface;

/**
 * Native PHP session implementation.
 */
class Session implements SessionInterface
{
    /**
     * Session key used to store all the flash data.
     */
    private const string FLASH_KEY = 'flash_data';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set(string $key, mixed $value): self
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): SessionInterface
    {
        unset($_SESSION[$key]);

        return $this;
    }

    public function setFlash(string $key, mixed $value): self
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;

        return $this;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $result = $_SESSION[self::FLASH_KEY][$key] ?? $default;

        unset($_SESSION[self::FLASH_KEY][$key]);

        if (empty($_SESSION[self::FLASH_KEY])) {
            unset($_SESSION[self::FLASH_KEY]);
        }

        return $result;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }
}