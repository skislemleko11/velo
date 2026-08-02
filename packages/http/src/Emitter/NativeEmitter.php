<?php
declare(strict_types=1);

namespace Velo\Http\Emitter;

use Velo\Http\Emitter\Interfaces\EmitterInterface;

class NativeEmitter implements EmitterInterface
{
    public function sendHeader(string $name, string $value): self
    {
        if (!headers_sent()) {
            header("$name: $value");
        }

        return $this;
    }

    public function sendHeaders(array $headers): EmitterInterface
    {
        if (!headers_sent()) {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }

        return $this;
    }

    public function setStatusCode(int $code): EmitterInterface
    {
        if (!headers_sent()) {
            http_response_code($code);
        }

        return $this;
    }

    public function terminate(): never
    {
        exit;
    }
}