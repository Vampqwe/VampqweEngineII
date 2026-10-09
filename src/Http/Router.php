<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use LogicException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Router
{
    private Dispatcher $dispatcher;

    /** @param list<array{0: string|list<string>, 1: string, 2: array{0: class-string, 1: string}}> $routes */
    public function __construct(
        private readonly ContainerInterface $container,
        array $routes,
    ) {
        $this->dispatcher = simpleDispatcher(static function (RouteCollector $collector) use ($routes): void {
            foreach ($routes as [$methods, $path, $handler]) {
                $collector->addRoute($methods, $path, $handler);
            }
        });
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

        if ($route[0] === Dispatcher::NOT_FOUND) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return new Response('Method Not Allowed', Response::HTTP_METHOD_NOT_ALLOWED, [
                'Allow' => implode(', ', $route[1]),
            ]);
        }

        [$controllerClass, $method] = $route[1];
        $controller = $this->container->get($controllerClass);
        $response = $controller->$method($request, $route[2]);

        if (!$response instanceof Response) {
            throw new LogicException('Route handlers must return an HTTP response.');
        }

        return $response;
    }
}