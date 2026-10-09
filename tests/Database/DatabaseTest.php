<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Database;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vampqwe\Engine\Database\Database;

final class DatabaseTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        $this->database = new Database(new PDO('sqlite::memory:'));
        $this->database->execute('CREATE TABLE records (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
    }

    public function testQueriesBindParametersAndFetchRows(): void
    {
        self::assertSame(1, $this->database->execute(
            'INSERT INTO records (value) VALUES (:value)',
            ['value' => 'sample'],
        ));

        self::assertSame(['value' => 'sample'], $this->database->fetchOne(
            'SELECT value FROM records WHERE id = :id',
            ['id' => 1],
        ));
        self::assertSame([], $this->database->fetchAll('SELECT value FROM records WHERE id = :id', ['id' => 99]));
    }

    public function testNestedTransactionCanRollBackToSavepoint(): void
    {
        $this->database->transaction(function (Database $database): void {
            $database->execute('INSERT INTO records (value) VALUES (:value)', ['value' => 'outer']);

            try {
                $database->transaction(function (Database $database): void {
                    $database->execute('INSERT INTO records (value) VALUES (:value)', ['value' => 'inner']);
                    throw new RuntimeException('Roll back nested work.');
                });
            } catch (RuntimeException) {
            }
        });

        self::assertSame(
            [['value' => 'outer']],
            $this->database->fetchAll('SELECT value FROM records'),
        );
    }

    public function testTransactionRollsBackWhenCallbackThrows(): void
    {
        try {
            $this->database->transaction(function (Database $database): void {
                $database->execute('INSERT INTO records (value) VALUES (:value)', ['value' => 'temporary']);
                throw new RuntimeException('Roll back transaction.');
            });
            self::fail('The callback exception should be rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Roll back transaction.', $exception->getMessage());
        }

        self::assertSame([], $this->database->fetchAll('SELECT value FROM records'));
    }
}