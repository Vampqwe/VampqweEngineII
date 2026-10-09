<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Database;

use PDO;
use PHPUnit\Framework\TestCase;
use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Database\MigrationRunner;
use Vampqwe\Engine\Repository\UserRepository;

final class UserRepositoryTest extends TestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        $database = new Database(new PDO('sqlite::memory:'));
        $runner = new MigrationRunner($database);
        $runner->migrate(dirname(__DIR__, 2) . '/database/migrations');
        $this->users = new UserRepository($database);
    }

    public function testUserCanBeCreatedFoundUpdatedAndDeleted(): void
    {
        $passwordHash = password_hash('correct horse battery staple', PASSWORD_DEFAULT);
        self::assertIsString($passwordHash);

        $user = $this->users->create('dev@example.test', $passwordHash);

        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $user->id);
        self::assertSame('dev@example.test', $user->email);
        self::assertTrue($user->verifyPassword('correct horse battery staple'));
        self::assertFalse($user->verifyPassword('incorrect password'));
        self::assertInstanceOf(\DateTimeImmutable::class, $user->createdAt);
        self::assertSame($user->id, $this->users->findByEmail('dev@example.test')?->id);

        $newPasswordHash = password_hash('new correct horse battery staple', PASSWORD_DEFAULT);
        self::assertIsString($newPasswordHash);
        $this->users->updatePasswordHash($user->id, $newPasswordHash);

        self::assertTrue($this->users->findById($user->id)?->verifyPassword('new correct horse battery staple'));
        self::assertTrue($this->users->delete($user->id));
        self::assertNull($this->users->findById($user->id));
        self::assertFalse($this->users->delete($user->id));
    }
}