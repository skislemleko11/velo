<?php
declare(strict_types=1);

namespace Velo\Session\Session\Interfaces;

interface SessionInterface
{
    /**
     * Stores a value in the session.
     */
    public function set(string $key, mixed $value): self;

    /**
     * Retrieves a value from the session.
     *
     * Returns the default value when the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Determines whether a session key exists.
     */
    public function has(string $key): bool;

    /**
     * Removes the given key from the session.
     */
    public function remove(string $key): self;

    /**
     * Stores flash data that will be available until it is retrieved.
     */
    public function setFlash(string $key, mixed $value): self;

    /**
     * Retrieves and removes flash data.
     *
     * Returns the default value when the key does not exist.
     */
    public function getFlash(string $key, mixed $default = null): mixed;

    /**
     * Determines whether a flash key exists.
     */
    public function hasFlash(string $key): bool;
}