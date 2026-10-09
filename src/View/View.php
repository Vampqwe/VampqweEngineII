<?php

declare(strict_types=1);

namespace Vampqwe\Engine\View;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class View
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], int $statusCode = Response::HTTP_OK): Response
    {
        return new Response($this->twig->render($template, $data), $statusCode, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}