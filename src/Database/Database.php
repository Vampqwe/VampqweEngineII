<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Database;

use PDO;
use PDOStatement;
use Throwable;

final class Database
{
    private int $transactionDepth = 0;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<int|string, mixed> $parameters
     *  @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->prepare($sql, $parameters)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<int|string, mixed> $parameters
     *  @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $parameters = []): ?array
    {
        $row = $this->prepare($sql, $parameters)->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $parameters */
    public function execute(string $sql, array $parameters = []): int
    {
        return $this->prepare($sql, $parameters)->rowCount();
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $level = $this->transactionDepth;
        $savepoint = 'engine_transaction_' . $level;

        if ($level === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec('SAVEPOINT ' . $savepoint);
        }

        $this->transactionDepth++;

        try {
            $result = $callback($this);

            if ($level === 0) {
                $this->pdo->commit();
            } else {
                $this->pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            }

            $this->transactionDepth = $level;

            return $result;
        } catch (Throwable $exception) {
            $this->transactionDepth = $level;

            if ($this->pdo->inTransaction()) {
                if ($level === 0) {
                    $this->pdo->rollBack();
                } else {
                    $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                    $this->pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
                }
            }

            throw $exception;
        }
    }

    /** @param array<int|string, mixed> $parameters */
    private function prepare(string $sql, array $parameters): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($parameters as $parameter => $value) {
            $key = is_int($parameter) ? $parameter + 1 : (str_starts_with($parameter, ':') ? $parameter : ':' . $parameter);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue($key, $value, $type);
        }

        $statement->execute();

        return $statement;
    }
}