<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Database\ConnectionFactory;
use Vampqwe\Engine\Http\Router;
use function DI\factory;

$root = dirname(__DIR__);
$settings = Dotenv\Dotenv::createImmutable($root, 'config.env')->safeLoad();
$config = new Config($settings);
date_default_timezone_set($config->getString('APP_TIMEZONE', 'UTC'));

$builder = new ContainerBuilder();
$builder->addDefinitions([
    Config::class => $config,
    LoggerInterface::class => factory(static function (Config $config) use ($root): LoggerInterface {
        $logger = new Logger($config->getString('APP_NAME', 'app'));
        $logger->pushHandler(new StreamHandler($root . '/var/log/app.log'));

        return $logger;
    }),
    Environment::class => factory(static function (Config $config) use ($root): Environment {
        $options = [
            'autoescape' => 'html',
            'strict_variables' => true,
            'debug' => $config->getBool('APP_DEBUG', false),
        ];

        if (!$config->getBool('APP_DEBUG', false)) {
            $options['cache'] = $root . '/var/cache/twig';
        }

        return new Environment(new FilesystemLoader($root . '/templates'), $options);
    }),
    PDO::class => factory(static fn (ConnectionFactory $factory): PDO => $factory->create()),
    Router::class => factory(static fn (ContainerInterface $container): Router => new Router(
        $container,
        require $root . '/config/routes.php',
    )),
]);

return $builder->build();