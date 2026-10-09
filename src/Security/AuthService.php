<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Security;

use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Model\User;
use Vampqwe\Engine\Repository\UserRepository;

final class AuthService
{
    private const SESSION_USER_ID = '_auth_user_id';

    private static ?string $dummyPasswordHash = null;

    public function __construct(
        private readonly UserRepository $users,
        private readonly CsrfTokenManager $csrfTokens,
        private readonly Config $config,
    ) {
    }

    public function register(string $email, string $password): User
    {
        $email = $this->normalizeEmail($email);
        $this->validatePassword($password);

        if ($this->users->findByEmail($email) !== null) {
            throw new UserAlreadyRegistered('Account could not be created with these details.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if (!is_string($passwordHash)) {
            throw new RuntimeException('Password could not be secured.');
        }

        try {
            $user = $this->users->create($email, $passwordHash);
        } catch (PDOException $exception) {
            if (str_starts_with((string) $exception->getCode(), '23')) {
                throw new UserAlreadyRegistered('Account could not be created with these details.', 0, $exception);
            }

            throw $exception;
        }

        $this->establishSession($user);

        return $user;
    }

    public function authenticate(string $email, string $password): bool
    {
        try {
            $email = $this->normalizeEmail($email);
        } catch (InvalidArgumentException) {
            $this->verifyDummyPassword($password);

            return false;
        }

        if (strlen($password) > $this->maximumPasswordBytes()) {
            $this->verifyDummyPassword($password);

            return false;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !$user->verifyPassword($password)) {
            if ($user === null) {
                $this->verifyDummyPassword($password);
            }

            return false;
        }

        if ($user->needsPasswordRehash()) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if (!is_string($passwordHash)) {
                throw new RuntimeException('Password could not be secured.');
            }

            $this->users->updatePasswordHash($user->id, $passwordHash);
        }

        $this->establishSession($user);

        return true;
    }

    public function currentUser(): ?User
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $id = $_SESSION[self::SESSION_USER_ID] ?? null;

        if (!is_string($id)) {
            return null;
        }

        $user = $this->users->findById($id);

        if ($user === null) {
            unset($_SESSION[self::SESSION_USER_ID]);
        }

        return $user;
    }

    public function logout(): void
    {
        $this->assertSessionStarted();
        unset($_SESSION[self::SESSION_USER_ID]);

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Session could not be regenerated.');
        }

        $this->csrfTokens->rotate();
    }

    private function establishSession(User $user): void
    {
        $this->assertSessionStarted();

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Session could not be regenerated.');
        }

        $_SESSION[self::SESSION_USER_ID] = $user->id;
        $this->csrfTokens->rotate();
    }

    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }

        return $email;
    }

    private function validatePassword(string $password): void
    {
        $minimum = $this->config->getInt('AUTH_PASSWORD_MIN_BYTES', 12, 1, 72);
        $maximum = $this->maximumPasswordBytes();
        $length = strlen($password);

        if (str_contains($password, "\0") || $length < $minimum || $length > $maximum) {
            throw new InvalidArgumentException(sprintf(
                'Password must contain between %d and %d bytes.',
                $minimum,
                $maximum,
            ));
        }
    }

    private function maximumPasswordBytes(): int
    {
        return $this->config->getInt('AUTH_PASSWORD_MAX_BYTES', 72, 12, 72);
    }

    private function verifyDummyPassword(string $password): void
    {
        self::$dummyPasswordHash ??= password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        if (is_string(self::$dummyPasswordHash)) {
            password_verify($password, self::$dummyPasswordHash);
        }
    }

    private function assertSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Start the session before using authentication.');
        }
    }
}