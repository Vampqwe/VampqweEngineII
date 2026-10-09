<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Database;

interface MigrationInterface
{
    public function up(Database $database): void;

    public function down(Database $database): void;
}