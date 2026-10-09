<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Model;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        private readonly string $passwordHash,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function needsPasswordRehash(): bool
    {
        return password_needs_rehash($this->passwordHash, PASSWORD_DEFAULT);
    }
}