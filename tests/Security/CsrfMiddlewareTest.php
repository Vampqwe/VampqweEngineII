<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vampqwe\Engine\Security\CsrfMiddleware;
use Vampqwe\Engine\Security\CsrfTokenManager;

final class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        session_write_close();
    }

    public function testSafeRequestPassesWithoutToken(): void
    {
        $response = $this->middleware()->process(
            Request::create('/', 'GET'),
            static fn (Request $request): Response => new Response('ok'),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testUnsafeRequestRequiresValidToken(): void
    {
        $tokens = new CsrfTokenManager();
        $middleware = new CsrfMiddleware($tokens);
        $next = static fn (Request $request): Response => new Response('ok');

        $invalidResponse = $middleware->process(
            Request::create('/', 'POST', ['_token' => 'invalid']),
            $next,
        );
        $validResponse = $middleware->process(
            Request::create('/', 'POST', ['_token' => $tokens->token()]),
            $next,
        );

        self::assertSame(419, $invalidResponse->getStatusCode());
        self::assertSame(Response::HTTP_OK, $validResponse->getStatusCode());
    }

    private function middleware(): CsrfMiddleware
    {
        return new CsrfMiddleware(new CsrfTokenManager());
    }
}