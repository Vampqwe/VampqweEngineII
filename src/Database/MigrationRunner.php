<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Database;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

final class MigrationRunner
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return list<string> */
    public function migrate(string $directory): array
    {
        $this->ensureMigrationTable();
        $applied = array_column($this->database->fetchAll('SELECT name FROM engine_migrations'), 'name');
        $lastBatch = $this->database->fetchOne('SELECT MAX(batch) AS batch FROM engine_migrations');
        $batch = (int) ($lastBatch['batch'] ?? 0) + 1;
        $migrated = [];

        foreach ($this->migrationFiles($directory) as $file) {
            $name = basename($file);

            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = $this->loadMigration($file);
            $migration->up($this->database);
            $this->database->execute(
                'INSERT INTO engine_migrations (name, batch) VALUES (:name, :batch)',
                ['name' => $name, 'batch' => $batch],
            );
            $migrated[] = $name;
        }

        return $migrated;
    }

    /** @return list<string> */
    public function rollbackLastBatch(string $directory): array
    {
        $this->ensureMigrationTable();
        $lastBatch = $this->database->fetchOne('SELECT MAX(batch) AS batch FROM engine_migrations');
        $batch = (int) ($lastBatch['batch'] ?? 0);

        if ($batch === 0) {
            return [];
        }

        $rows = $this->database->fetchAll(
            'SELECT name FROM engine_migrations WHERE batch = :batch ORDER BY name DESC',
            ['batch' => $batch],
        );
        $rolledBack = [];

        foreach ($rows as $row) {
            $name = $row['name'];

            if (!is_string($name) || basename($name) !== $name) {
                throw new UnexpectedValueException('Invalid migration name in migration table.');
            }

            $file = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

            if (!is_file($file)) {
                throw new RuntimeException(sprintf('Migration file "%s" is missing.', $name));
            }

            $this->loadMigration($file)->down($this->database);
            $this->database->execute('DELETE FROM engine_migrations WHERE name = :name', ['name' => $name]);
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    private function ensureMigrationTable(): void
    {
        $this->database->execute(
            'CREATE TABLE IF NOT EXISTS engine_migrations ('
            . 'name VARCHAR(255) PRIMARY KEY, '
            . 'batch INTEGER NOT NULL, '
            . 'migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            . ')',
        );
    }

    /** @return list<string> */
    private function migrationFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Migration directory "%s" does not exist.', $directory));
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        return $files;
    }

    private function loadMigration(string $file): MigrationInterface
    {
        $migration = require $file;

        if (!$migration instanceof MigrationInterface) {
            throw new UnexpectedValueException(sprintf('Migration "%s" must return a MigrationInterface instance.', basename($file)));
        }

        return $migration;
    }
}