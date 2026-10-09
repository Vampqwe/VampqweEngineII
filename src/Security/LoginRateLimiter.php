<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Vampqwe\Engine\Config\Config;

final class LoginRateLimiter
{
    private RateLimiterFactory $accountLimiters;

    private RateLimiterFactory $ipLimiters;

    private RateLimiterFactory $registrationLimiters;

    public function __construct(CacheItemPoolInterface $cache, Config $config)
    {
        $storage = new CacheStorage($cache);
        $window = $config->getInt('AUTH_RATE_LIMIT_WINDOW_SECONDS', 900, 1, 86400);

        $this->accountLimiters = $this->createFactory(
            'login_account',
            $config->getInt('AUTH_LOGIN_LIMIT_PER_ACCOUNT', 5, 1, 1000),
            $window,
            $storage,
        );
        $this->ipLimiters = $this->createFactory(
            'login_ip',
            $config->getInt('AUTH_LOGIN_LIMIT_PER_IP', 30, 1, 10000),
            $window,
            $storage,
        );
        $this->registrationLimiters = $this->createFactory(
            'register_ip',
            $config->getInt('AUTH_REGISTER_LIMIT_PER_IP', 5, 1, 1000),
            $window,
            $storage,
        );
    }

    public function consumeLogin(string $email, ?string $ipAddress): ?int
    {
        $email = strtolower(substr(trim($email), 0, 254));
        $ipAddress ??= 'unknown';

        $accountLimit = $this->accountLimiters->create('login-account:' . $email)->consume();
        $ipLimit = $this->ipLimiters->create('login-ip:' . $ipAddress)->consume();

        return max($this->retryAfter($accountLimit), $this->retryAfter($ipLimit)) ?: null;
    }

    public function resetAccount(string $email): void
    {
        $email = strtolower(substr(trim($email), 0, 254));
        $this->accountLimiters->create('login-account:' . $email)->reset();
    }

    public function consumeRegistration(?string $ipAddress): ?int
    {
        $ipAddress ??= 'unknown';
        $limit = $this->registrationLimiters->create('register-ip:' . $ipAddress)->consume();
        $retryAfter = $this->retryAfter($limit);

        return $retryAfter === 0 ? null : $retryAfter;
    }

    private function createFactory(string $id, int $limit, int $window, CacheStorage $storage): RateLimiterFactory
    {
        return new RateLimiterFactory([
            'id' => $id,
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => sprintf('%d seconds', $window),
        ], $storage);
    }

    private function retryAfter(RateLimit $limit): int
    {
        if ($limit->isAccepted()) {
            return 0;
        }

        return max(1, $limit->getRetryAfter()->getTimestamp() - time());
    }
}