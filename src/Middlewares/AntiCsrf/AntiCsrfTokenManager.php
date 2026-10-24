<?php
declare(strict_types=1);

namespace Velo\Middlewares\AntiCsrf;

use Random\RandomException;
use Velo\Session\Session\SessionInterface;

final class AntiCsrfTokenManager implements AntiCsrfTokenManagerInterface
{
    public function __construct(
        private readonly SessionInterface $session
    )
    {
    }

    public function getToken(): string
    {
        $token = $this->session->get(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME);

        if (!is_string($token) || $token === '') {
            return $this->regenerateToken();
        }

        return $token;
    }

    /**
     * @throws RandomException
     */
    public function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set(
            AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME,
            $token
        );

        return $token;
    }

    public function isValid(mixed $candidate): bool
    {
        $sessionToken = $this->session->get(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME);

        return is_string($sessionToken)
            && is_string($candidate)
            && hash_equals($sessionToken, $candidate);
    }
}