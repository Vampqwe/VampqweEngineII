<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Repository;

use DateTimeImmutable;
use RuntimeException;
use UnexpectedValueException;
use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Model\User;

final class UserRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function create(string $email, string $passwordHash): User
    {
        $id = self::createId();
        $this->database->execute(
            'INSERT INTO users (id, email, password_hash) VALUES (:id, :email, :password_hash)',
            [
                'id' => $id,
                'email' => $email,
                'password_hash' => $passwordHash,
            ],
        );

        return $this->findById($id) ?? throw new RuntimeException('Created user could not be loaded.');
    }

    public function findById(string $id): ?User
    {
        $row = $this->database->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->database->fetchOne('SELECT * FROM users WHERE email = :email', ['email' => $email]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function updatePasswordHash(string $id, string $passwordHash): void
    {
        $this->database->execute(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            [
                'id' => $id,
                'password_hash' => $passwordHash,
            ],
        );
    }

    public function delete(string $id): bool
    {
        return $this->database->execute('DELETE FROM users WHERE id = :id', ['id' => $id]) === 1;
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        foreach (['id', 'email', 'password_hash', 'created_at'] as $field) {
            if (!isset($row[$field]) || !is_string($row[$field])) {
                throw new UnexpectedValueException(sprintf('User row is missing a valid "%s" field.', $field));
            }
        }

        return new User(
            $row['id'],
            $row['email'],
            $row['password_hash'],
            new DateTimeImmutable($row['created_at']),
        );
    }

    private static function createId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}