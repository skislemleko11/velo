<?php
declare(strict_types=1);

namespace Velo\Core;

readonly class Config
{
    public function __construct(private array $data = [])
    {
    }

    public function get(string $key, $default = null)
    {
        if (isset($this->data[$key]))
            return $this->data[$key];
        return $default;
    }
}