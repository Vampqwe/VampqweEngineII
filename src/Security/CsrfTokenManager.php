<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Security;

use LogicException;

final class CsrfTokenManager
{
    public function token(string $id = 'default'): string
    {
        $this->assertSessionStarted();
        $_SESSION['_csrf_tokens'] ??= [];

        if (!isset($_SESSION['_csrf_tokens'][$id]) || !is_string($_SESSION['_csrf_tokens'][$id])) {
            $_SESSION['_csrf_tokens'][$id] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_tokens'][$id];
    }

    public function isValid(string $submittedToken, string $id = 'default'): bool
    {
        $this->assertSessionStarted();
        $knownToken = $_SESSION['_csrf_tokens'][$id] ?? null;

        return is_string($knownToken) && hash_equals($knownToken, $submittedToken);
    }

    private function assertSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new LogicException('Start the session before using CSRF tokens.');
        }
    }
}