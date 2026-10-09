<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Http\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';
header_remove('X-Powered-By');

$container = require dirname(__DIR__) . '/bootstrap/app.php';
$config = $container->get(Config::class);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $config->getBool('SESSION_SECURE_COOKIE', false),
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

$container->get(Kernel::class)
    ->handle(Request::createFromGlobals())
    ->send();