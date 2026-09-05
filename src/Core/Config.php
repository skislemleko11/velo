<?php
declare(strict_types=1);

namespace Velo\Core;

/**
 * Facade for the given array made for configuration purposes.
 */
readonly class Config
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data = [])
    {
    }

    /**
     * Gets the value for the given key, if the key is not set, returns $default parameter value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->data[$key]))
            return $this->data[$key];

        return $default;
    }
}