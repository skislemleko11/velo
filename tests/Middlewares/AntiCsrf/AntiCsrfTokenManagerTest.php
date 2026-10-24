<?php
declare(strict_types=1);

namespace Velo\Tests\Middlewares\AntiCsrf;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Middlewares\AntiCsrf\AntiCsrfMiddleware;
use Velo\Middlewares\AntiCsrf\AntiCsrfTokenManager;
use Velo\Session\Session\SessionInterface;

final class AntiCsrfTokenManagerTest extends TestCase
{
    #[Test]
    public function it_returns_the_token_from_session_when_present(): void
    {
        $session = self::createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME)
            ->willReturn('existing-token');

        $session->expects(self::never())
            ->method('set');

        $manager = new AntiCsrfTokenManager($session);

        self::assertSame('existing-token', $manager->getToken());
    }

    #[Test]
    public function it_generates_and_returns_new_token_when_session_token_is_missing(): void
    {
        $session = self::createMock(SessionInterface::class);
        $generatedToken = null;

        $session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME)
            ->willReturn(null);

        $session->expects(self::once())
            ->method('set')
            ->with(
                AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME,
                self::callback(static function (mixed $token) use (&$generatedToken): bool {
                    $generatedToken = $token;

                    return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
                })
            )
            ->willReturnSelf();

        $manager = new AntiCsrfTokenManager($session);

        $token = $manager->getToken();

        self::assertSame($token, $generatedToken);
        self::assertIsString($token);
        self::assertSame(64, strlen($token));
    }

    #[Test]
    public function it_generates_and_returns_new_token_when_session_token_is_empty_string(): void
    {
        $session = self::createMock(SessionInterface::class);
        $generatedToken = null;

        $session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME)
            ->willReturn('');

        $session->expects(self::once())
            ->method('set')
            ->with(
                AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME,
                self::callback(static function (mixed $token) use (&$generatedToken): bool {
                    $generatedToken = $token;

                    return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
                })
            )
            ->willReturnSelf();

        $manager = new AntiCsrfTokenManager($session);

        $token = $manager->getToken();

        self::assertSame($token, $generatedToken);
        self::assertIsString($token);
        self::assertSame(64, strlen($token));
    }

    #[Test]
    public function it_regenerates_the_token_in_session(): void
    {
        $session = self::createMock(SessionInterface::class);
        $generatedToken = null;

        $session->expects(self::once())
            ->method('set')
            ->with(
                AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME,
                self::callback(static function (mixed $token) use (&$generatedToken): bool {
                    $generatedToken = $token;

                    return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
                })
            )
            ->willReturnSelf();

        $manager = new AntiCsrfTokenManager($session);

        $manager->regenerateToken();

        self::assertIsString($generatedToken);
        self::assertSame(64, strlen($generatedToken));
    }

    #[Test]
    #[DataProvider('validTokensProvider')]
    public function it_validates_matching_tokens(string $sessionToken, string $candidate): void
    {
        $session = self::createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME)
            ->willReturn($sessionToken);

        $manager = new AntiCsrfTokenManager($session);

        self::assertTrue($manager->isValid($candidate));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function validTokensProvider(): array
    {
        return [
            'same_literal_token' => ['token-123', 'token-123'],
            'same_hex_token' => ['a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6', 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6'],
        ];
    }

    #[Test]
    #[DataProvider('invalidTokensProvider')]
    public function it_rejects_invalid_tokens(mixed $sessionToken, mixed $candidate): void
    {
        $session = self::createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('get')
            ->with(AntiCsrfMiddleware::CSRF_SESSION_TOKEN_NAME)
            ->willReturn($sessionToken);

        $manager = new AntiCsrfTokenManager($session);

        self::assertFalse($manager->isValid($candidate));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function invalidTokensProvider(): array
    {
        return [
            'session_missing_candidate_valid' => [null, 'token-123'],
            'session_empty_candidate_valid' => ['', 'token-123'],
            'session_valid_candidate_missing' => ['token-123', null],
            'session_valid_candidate_empty' => ['token-123', ''],
            'session_valid_candidate_different' => ['token-123', 'token-456'],
            'session_int_candidate_string' => [123, 'token-123'],
            'session_string_candidate_int' => ['token-123', 123],
            'session_bool_candidate_string' => [true, 'token-123'],
            'session_string_candidate_bool' => ['token-123', true],
            'session_array_candidate_string' => [['token'], 'token-123'],
            'session_string_candidate_array' => ['token-123', ['token']],
        ];
    }
}
