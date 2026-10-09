<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Database;

use PDO;
use PHPUnit\Framework\TestCase;
use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Database\MigrationRunner;

final class MigrationRunnerTest extends TestCase
{
    private Database $database;

    private MigrationRunner $runner;

    private string $directory;

    protected function setUp(): void
    {
        $this->database = new Database(new PDO('sqlite::memory:'));
        $this->runner = new MigrationRunner($this->database);
        $this->directory = dirname(__DIR__, 2) . '/database/migrations';
    }

    public function testMigrationsAreAppliedOnceAndCanBeRolledBack(): void
    {
        $name = '20261009000000_create_users_table.php';

        self::assertSame([$name], $this->runner->migrate($this->directory));
        self::assertSame([], $this->runner->migrate($this->directory));
        self::assertNotNull($this->database->fetchOne("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users'"));

        self::assertSame([$name], $this->runner->rollbackLastBatch($this->directory));
        self::assertNull($this->database->fetchOne("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users'"));
    }
}