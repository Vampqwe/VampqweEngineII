<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Controller;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Security\AuthService;
use Vampqwe\Engine\Security\LoginRateLimiter;
use Vampqwe\Engine\Security\UserAlreadyRegistered;
use Vampqwe\Engine\View\View;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Config $config,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly View $view,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function registerForm(Request $request, array $parameters = []): Response
    {
        if ($this->auth->currentUser() !== null) {
            return new RedirectResponse('/account');
        }

        return $this->renderForm('auth/register.twig');
    }

    /** @param array<string, string> $parameters */
    public function register(Request $request, array $parameters = []): Response
    {
        $retryAfter = $this->rateLimiter->consumeRegistration($request->getClientIp());

        if ($retryAfter !== null) {
            return $this->rateLimitedResponse($retryAfter);
        }

        $data = $request->request->all();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $confirmation = $data['password_confirmation'] ?? null;

        if (!is_string($email) || !is_string($password) || !is_string($confirmation) || $password !== $confirmation) {
            return $this->renderForm('auth/register.twig', 'Проверьте введённые данные.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->auth->register($email, $password);
        } catch (InvalidArgumentException|UserAlreadyRegistered) {
            return $this->renderForm('auth/register.twig', 'Не удалось создать аккаунт. Проверьте email и пароль.', Response::HTTP_UNPROCESSABLE_ENTITY, $email);
        }

        return new RedirectResponse('/account');
    }

    /** @param array<string, string> $parameters */
    public function loginForm(Request $request, array $parameters = []): Response
    {
        if ($this->auth->currentUser() !== null) {
            return new RedirectResponse('/account');
        }

        return $this->renderForm('auth/login.twig');
    }

    /** @param array<string, string> $parameters */
    public function login(Request $request, array $parameters = []): Response
    {
        $data = $request->request->all();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $emailForLimit = is_string($email) ? $email : '';
        $retryAfter = $this->rateLimiter->consumeLogin($emailForLimit, $request->getClientIp());

        if ($retryAfter !== null) {
            return $this->rateLimitedResponse($retryAfter);
        }

        if (!is_string($email) || !is_string($password) || !$this->auth->authenticate($email, $password)) {
            return $this->renderForm('auth/login.twig', 'Неверный email или пароль.', Response::HTTP_UNPROCESSABLE_ENTITY, is_string($email) ? $email : '');
        }

        $this->rateLimiter->resetAccount($email);

        return new RedirectResponse('/account');
    }

    /** @param array<string, string> $parameters */
    public function logout(Request $request, array $parameters = []): Response
    {
        $this->auth->logout();

        return new RedirectResponse('/');
    }

    /** @param array<string, string> $parameters */
    public function account(Request $request, array $parameters = []): Response
    {
        $user = $this->auth->currentUser();

        if ($user === null) {
            return new RedirectResponse('/login');
        }

        return $this->view->render('auth/account.twig', [
            'appName' => $this->config->getString('APP_NAME', 'Vampqwe Engine'),
            'user' => $user,
        ]);
    }

    private function renderForm(
        string $template,
        ?string $error = null,
        int $statusCode = Response::HTTP_OK,
        string $email = '',
    ): Response {
        return $this->view->render($template, [
            'appName' => $this->config->getString('APP_NAME', 'Vampqwe Engine'),
            'error' => $error,
            'email' => $email,
        ], $statusCode);
    }

    private function rateLimitedResponse(int $retryAfter): Response
    {
        return new Response('Слишком много попыток. Повторите позже.', Response::HTTP_TOO_MANY_REQUESTS, [
            'Retry-After' => (string) $retryAfter,
        ]);
    }
}