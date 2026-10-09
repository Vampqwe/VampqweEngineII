<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\View\View;

final class HomeController
{
    public function __construct(
        private readonly View $view,
        private readonly Config $config,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function index(Request $request, array $parameters = []): Response
    {
        return $this->view->render('home.twig', [
            'appName' => $this->config->getString('APP_NAME', 'Vampqwe Engine'),
        ]);
    }
}