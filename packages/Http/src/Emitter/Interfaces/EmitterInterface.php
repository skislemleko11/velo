<?php
declare(strict_types=1);

namespace Velo\Http\Emitter\Interfaces;

interface EmitterInterface
{
    /**
     * Sets the given header if headers are not sent.
     */
    public function sendHeader(string $name, string $value): self;

    /**
     * Sets the given array of headers if headers are not sent.
     *
     * @param list<array<string, string>> $headers
     */
    public function sendHeaders(array $headers): self;

    /**
     * Sets status code if headers are not sent.
     */
    public function setStatusCode(int $code): self;

    /**
     * Terminates the script.
     */
    public function terminate(): never;
}