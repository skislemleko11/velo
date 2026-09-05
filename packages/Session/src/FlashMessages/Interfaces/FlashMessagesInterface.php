<?php
declare(strict_types=1);

namespace Velo\Session\FlashMessages\Interfaces;

interface FlashMessagesInterface
{
    /**
     * Adds a flash message.
     */
    public function add(string $type, string $value): self;

    /**
     * Retrieves and removes all flash messages of the given type.
     *
     * @param list<string> $default
     * @return list<string>
     */
    public function get(string $type, array $default = []): array;

    /**
     * Determines whether the given flash message type exists.
     */
    public function has(string $type): bool;

    /**
     * Retrieves and removes all flash messages.
     *
     * @return array<string, list<string>>
     */
    public function getAll(): array;

    /**
     * Adds a success flash message.
     */
    public function success(string $message): self;

    /**
     * Adds an error flash message.
     */
    public function error(string $message): self;

    /**
     * Adds a warning flash message.
     */
    public function warning(string $message): self;

    /**
     * Adds an info flash message.
     */
    public function info(string $message): self;
}