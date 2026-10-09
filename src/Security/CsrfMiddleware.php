<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vampqwe\Engine\Http\MiddlewareInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

    public function __construct(private readonly CsrfTokenManager $tokens)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $submittedToken = $request->headers->get('X-CSRF-TOKEN');
        $submittedToken ??= $request->request->all()['_token'] ?? null;

        if (!is_string($submittedToken) || !$this->tokens->isValid($submittedToken)) {
            return new Response('CSRF token validation failed.', 419);
        }

        return $next($request);
    }
}