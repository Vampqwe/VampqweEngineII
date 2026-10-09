<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Database;

use PDO;
use Vampqwe\Engine\Config\Config;

final class ConnectionFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(): PDO
    {
        $host = $this->config->getString('MYSQL_HOST', '127.0.0.1');
        $port = $this->config->getString('MYSQL_PORT', '3306');
        $database = $this->config->getString('MYSQL_DATABASE');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        return new PDO($dsn, $this->config->getString('MYSQL_USERNAME'), $this->config->getString('MYSQL_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}