<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Security;

use LogicException;

final class CsrfTokenManager
{
    public function token(string $id = 'default'): string
    {
        $this->assertSessionStarted();
        $this->ensureTokenStorage();

        if (!isset($_SESSION['_csrf_tokens'][$id]) || !is_string($_SESSION['_csrf_tokens'][$id])) {
            $_SESSION['_csrf_tokens'][$id] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_tokens'][$id];
    }

    public function isValid(string $submittedToken, string $id = 'default'): bool
    {
        $this->assertSessionStarted();
        $this->ensureTokenStorage();
        $knownToken = $_SESSION['_csrf_tokens'][$id] ?? null;

        return is_string($knownToken) && hash_equals($knownToken, $submittedToken);
    }

    public function rotate(string $id = 'default'): string
    {
        $this->assertSessionStarted();
        $this->ensureTokenStorage();
        unset($_SESSION['_csrf_tokens'][$id]);

        return $this->token($id);
    }

    private function ensureTokenStorage(): void
    {
        if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
            $_SESSION['_csrf_tokens'] = [];
        }
    }

    private function assertSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new LogicException('Start the session before using CSRF tokens.');
        }
    }
}