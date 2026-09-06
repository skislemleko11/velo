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
    public const string SUCCESS = 'success';
    public const string ERROR = 'error';
    public const string WARNING = 'warning';
    public const string INFO = 'info';

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
        $flashMessages = $this->getFromSession();

        $flashMessages[$type][] = $value;

        $this->session->set(self::SESSION_KEY, $flashMessages);

        return $this;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getFromSession(): array
    {
        /** @var array<string, list<string>> */
        return $this->session->get(self::SESSION_KEY, []);
    }

    /**
     * @param list<string> $default
     * @return list<string>
     */
    public function get(string $type, array $default = []): array
    {
        $flashMessages = $this->getFromSession();

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
        $flashMessages = $this->getFromSession();

        return !empty($flashMessages[$type]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getAll(): array
    {
        $flashMessages = $this->getFromSession();

        $this->session->remove(self::SESSION_KEY);

        return $flashMessages;
    }

    public function success(string $message): self
    {
        return $this->add(self::SUCCESS, $message);
    }

    public function error(string $message): self
    {
        return $this->add(self::ERROR, $message);
    }

    public function warning(string $message): self
    {
        return $this->add(self::WARNING, $message);
    }

    public function info(string $message): self
    {
        return $this->add(self::INFO, $message);
    }
}