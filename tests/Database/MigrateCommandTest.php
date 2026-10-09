<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Database;

use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Database\MigrateCommand;
use Vampqwe\Engine\Database\MigrationRunner;

final class MigrateCommandTest extends TestCase
{
    public function testConsoleCommandAppliesMigrations(): void
    {
        $database = new Database(new PDO('sqlite::memory:'));
        $runner = new MigrationRunner($database);
        $directory = dirname(__DIR__, 2) . '/database/migrations';
        $application = new Application('Vampqwe Engine');
        $application->setAutoExit(false);
        $application->add(new MigrateCommand($runner, $directory));
        $output = new BufferedOutput();

        $exitCode = $application->run(new ArrayInput(['command' => 'db:migrate']), $output);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Applied 20261009000000_create_users_table.php', $output->fetch());
    }
}