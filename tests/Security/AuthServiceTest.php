<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Security;

use PDO;
use PHPUnit\Framework\TestCase;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Database\MigrationRunner;
use Vampqwe\Engine\Repository\UserRepository;
use Vampqwe\Engine\Security\AuthService;
use Vampqwe\Engine\Security\CsrfTokenManager;
use Vampqwe\Engine\Security\UserAlreadyRegistered;

final class AuthServiceTest extends TestCase
{
    private AuthService $auth;

    private UserRepository $users;

    private CsrfTokenManager $csrfTokens;

    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        $database = new Database(new PDO('sqlite::memory:'));
        (new MigrationRunner($database))->migrate(dirname(__DIR__, 2) . '/database/migrations');
        $this->users = new UserRepository($database);
        $this->csrfTokens = new CsrfTokenManager();
        $this->auth = new AuthService($this->users, $this->csrfTokens, new Config([
            'AUTH_PASSWORD_MIN_BYTES' => '12',
            'AUTH_PASSWORD_MAX_BYTES' => '72',
        ]));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        session_write_close();
    }

    public function testRegistrationAndAuthenticationRegenerateSessionAndCsrfToken(): void
    {
        $previousSessionId = session_id();
        $oldCsrfToken = $this->csrfTokens->token();
        $user = $this->auth->register('  Dev@Example.test ', 'correct horse battery staple');

        self::assertSame('dev@example.test', $user->email);
        self::assertNotSame($previousSessionId, session_id());
        self::assertFalse($this->csrfTokens->isValid($oldCsrfToken));
        self::assertSame($user->id, $this->auth->currentUser()?->id);

        $this->auth->logout();
        self::assertNull($this->auth->currentUser());
        self::assertFalse($this->auth->authenticate('dev@example.test', 'wrong password'));
        self::assertTrue($this->auth->authenticate('DEV@example.test', 'correct horse battery staple'));
        self::assertSame($user->id, $this->auth->currentUser()?->id);
    }

    public function testRegistrationRejectsDuplicateAndWeakCredentials(): void
    {
        $this->auth->register('dev@example.test', 'correct horse battery staple');

        try {
            $this->auth->register('DEV@example.test', 'correct horse battery staple');
            self::fail('Duplicate email must be rejected.');
        } catch (UserAlreadyRegistered) {
            self::assertTrue(true);
        }

        try {
            $this->auth->register('other@example.test', 'short');
            self::fail('Short passwords must be rejected.');
        } catch (\InvalidArgumentException) {
            self::assertTrue(true);
        }
    }
}