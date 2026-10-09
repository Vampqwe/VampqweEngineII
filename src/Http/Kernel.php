<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Vampqwe\Engine\Config\Config;

final class Kernel
{
    public function __construct(
        private readonly Router $router,
        private readonly MiddlewareStack $middleware,
        private readonly LoggerInterface $logger,
        private readonly Config $config,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->middleware->handle(
                $request,
                fn (Request $request): Response => $this->router->dispatch($request),
            );
        } catch (Throwable $exception) {
            $this->logger->error('Unhandled application exception.', ['exception' => $exception]);
            $message = $this->config->getBool('APP_DEBUG', false)
                ? htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : 'Internal Server Error';
            $response = new Response($message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response->headers->set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}