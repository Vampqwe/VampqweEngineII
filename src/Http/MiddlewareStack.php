<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MiddlewareStack
{
    /** @param list<MiddlewareInterface> $middleware */
    public function __construct(private readonly array $middleware)
    {
    }

    /** @param callable(Request): Response $destination */
    public function handle(Request $request, callable $destination): Response
    {
        $next = $destination;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn (Request $request): Response => $middleware->process($request, $next);
        }

        return $next($request);
    }
}