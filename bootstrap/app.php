<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Database\Database;
use Vampqwe\Engine\Database\ConnectionFactory;
use Vampqwe\Engine\Database\MigrateCommand;
use Vampqwe\Engine\Database\MigrationRunner;
use Vampqwe\Engine\Http\MiddlewareStack;
use Vampqwe\Engine\Http\Router;
use Vampqwe\Engine\Security\CsrfMiddleware;
use Vampqwe\Engine\Security\CsrfTokenManager;
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
    Environment::class => factory(static function (Config $config, CsrfTokenManager $csrf) use ($root): Environment {
        $options = [
            'autoescape' => 'html',
            'strict_variables' => true,
            'debug' => $config->getBool('APP_DEBUG', false),
        ];

        if (!$config->getBool('APP_DEBUG', false)) {
            $options['cache'] = $root . '/var/cache/twig';
        }

        $twig = new Environment(new FilesystemLoader($root . '/templates'), $options);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => $csrf->token()));

        return $twig;
    }),
    MiddlewareStack::class => factory(static fn (CsrfMiddleware $csrf): MiddlewareStack => new MiddlewareStack([$csrf])),
    PDO::class => factory(static fn (ConnectionFactory $factory): PDO => $factory->create()),
    Database::class => factory(static fn (PDO $pdo): Database => new Database($pdo)),
    MigrateCommand::class => factory(static function (MigrationRunner $runner, Config $config) use ($root): MigrateCommand {
        $path = $config->getString('MIGRATIONS_PATH', 'database/migrations');
        $path = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : $root . '/' . $path;

        return new MigrateCommand($runner, $path);
    }),
    Router::class => factory(static fn (ContainerInterface $container): Router => new Router(
        $container,
        require $root . '/config/routes.php',
    )),
]);

return $builder->build();