<?php

declare(strict_types=1);

use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Database\MigrationInterface;

return new class implements MigrationInterface {
    public function up(Database $database): void
    {
        $database->execute(
            'CREATE TABLE users ('
            . 'id CHAR(36) PRIMARY KEY, '
            . 'email VARCHAR(254) NOT NULL UNIQUE, '
            . 'password_hash VARCHAR(255) NOT NULL, '
            . 'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            . ')',
        );
    }

    public function down(Database $database): void
    {
        $database->execute('DROP TABLE users');
    }
};