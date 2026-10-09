<?php

declare(strict_types=1);

use Vampqwe\Engine\Controller\HomeController;
use Vampqwe\Engine\Controller\AuthController;

return [
    ['GET', '/', [HomeController::class, 'index']],
    ['GET', '/register', [AuthController::class, 'registerForm']],
    ['POST', '/register', [AuthController::class, 'register']],
    ['GET', '/login', [AuthController::class, 'loginForm']],
    ['POST', '/login', [AuthController::class, 'login']],
    ['POST', '/logout', [AuthController::class, 'logout']],
    ['GET', '/account', [AuthController::class, 'account']],
];