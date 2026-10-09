<?php

declare(strict_types=1);

use Vampqwe\Engine\Controller\HomeController;

return [
    ['GET', '/', [HomeController::class, 'index']],
];