<?php
declare(strict_types=1);

namespace Velo\Session\FlashMessages;

use Velo\Session\FlashMessages\Interfaces\FlashMessagesInterface;
use Velo\Session\Session\Interfaces\SessionInterface;

/**
 * Manages flash messages stored in the session.
 */
readonly class FlashMessages implements FlashMessagesInterface
{
    /**
     * Session key used to store all the flash messages.
     */
    private const string SESSION_KEY = 'flash_messages';

    /**
     * @param SessionInterface $session Session storage implementation.
     */
    public function __construct(
        private SessionInterface $session
    )
    {
    }

    public function add(string $type, string $value): self
    {
        $flashMessages = $this->session->get(self::SESSION_KEY, []);

        $flashMessages[$type][] = $value;

        $this->session->set(self::SESSION_KEY, $flashMessages);

        return $this;
    }

    public function get(string $type, array $default = []): array
    {
        $flashMessages = $this->session->get(self::SESSION_KEY, []);

        if (!isset($flashMessages[$type])) {
            return $default;
        }

        $requestedMessages = $flashMessages[$type];

        unset($flashMessages[$type]);

        if (!$flashMessages) {
            $this->session->remove(self::SESSION_KEY);
        } else {
            $this->session->set(self::SESSION_KEY, $flashMessages);
        }

        return $requestedMessages;
    }

    public function has(string $type): bool
    {
        $flashMessages = $this->session->get(self::SESSION_KEY, []);

        return !empty($flashMessages[$type]);
    }

    public function getAll(): array
    {
        $flashMessages = $this->session->get(self::SESSION_KEY, []);

        $this->session->remove(self::SESSION_KEY);

        return $flashMessages;
    }

    public function success(string $message): self
    {
        return $this->add('success', $message);
    }

    public function error(string $message): self
    {
        return $this->add('error', $message);
    }

    public function warning(string $message): self
    {
        return $this->add('warning', $message);
    }

    public function info(string $message): self
    {
        return $this->add('info', $message);
    }
}